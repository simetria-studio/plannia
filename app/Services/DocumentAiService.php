<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\Student;
use App\Support\PaeeSkills;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DocumentAiService
{
    public function __construct(
        private DocumentTextExtractor $extractor
    ) {}

    /**
     * @param  array<int, array{type: string, path: string, original_name: string}>  $attachments
     * @return array{content: array<string, mixed>, sources: array<string, string>}
     */
    public function generateDocumentContent(Student $student, DocumentType $type, array $attachments): array
    {
        if ($type === DocumentType::PeiPaee) {
            throw new RuntimeException('Use generateCombinedContents para PEI+PAEE.');
        }

        $batch = $this->generateContentsForTypes($student, [$type], $attachments);

        return [
            'content' => $batch['contents'][$type->value],
            'sources' => $batch['sources'],
        ];
    }

    /**
     * Gera PEI e/ou PAEE reutilizando o mesmo OCR dos anexos.
     *
     * @param  array<int, DocumentType>  $types
     * @param  array<int, array{type: string, path: string, original_name: string}>  $attachments
     * @return array{contents: array<string, array<string, mixed>>, sources: array<string, string>}
     */
    public function generateContentsForTypes(Student $student, array $types, array $attachments): array
    {
        $prepared = $this->extractor->prepareAttachments($attachments);
        $contents = [];

        if (! config('services.openai.api_key')) {
            $sources = $this->sourcesPreview($prepared);

            foreach ($types as $type) {
                $contents[$type->value] = $this->fallbackContent(
                    $student,
                    $type,
                    $sources,
                    'Chave OPENAI_API_KEY não configurada no .env.'
                );
            }

            return ['contents' => $contents, 'sources' => $sources];
        }

        try {
            $extractedTexts = $this->extractTextsWithAi($prepared);
            $sources = $this->sanitizeSourcesForStorage($extractedTexts);

            foreach ($types as $type) {
                $contents[$type->value] = $this->callOpenAiForDocument($student, $type, $extractedTexts);
            }

            return ['contents' => $contents, 'sources' => $sources];
        } catch (\Throwable $e) {
            Log::error('Falha na geração IA do documento', [
                'student_id' => $student->id,
                'types' => array_map(fn (DocumentType $t) => $t->value, $types),
                'error' => $e->getMessage(),
            ]);

            $sources = $this->sourcesPreview($prepared);

            foreach ($types as $type) {
                $contents[$type->value] = $this->fallbackContent(
                    $student,
                    $type,
                    $sources,
                    'Falha na chamada à IA: '.$e->getMessage()
                );
            }

            return ['contents' => $contents, 'sources' => $sources];
        }
    }

    /**
     * @param  array<int, array{type: string, label: string, kind: string, text: string, path: ?string, mime: ?string, original_name: string}>  $prepared
     * @return array<string, string>
     */
    private function extractTextsWithAi(array $prepared): array
    {
        $texts = [];

        foreach ($prepared as $item) {
            $label = $item['label'];

            if ($item['kind'] === 'text') {
                $texts[$label] = $item['text'];

                continue;
            }

            if ($item['kind'] === 'image' && $item['path'] && $item['mime']) {
                $compressed = $this->extractor->compressImageForVision($item['path'], $item['mime']);

                if (! $compressed) {
                    $texts[$label] = '[Não foi possível processar a imagem '.$item['original_name'].']';

                    continue;
                }

                Log::info('OCR de documento por visão', [
                    'label' => $label,
                    'file' => $item['original_name'],
                    'bytes_b64' => strlen($compressed['base64']),
                ]);

                $texts[$label] = $this->ocrImage(
                    $label,
                    $item['original_name'],
                    $compressed['base64'],
                    $compressed['mime']
                );
            }
        }

        return $texts;
    }

    private function ocrImage(string $label, string $originalName, string $base64, string $mime): string
    {
        $prompt = <<<PROMPT
Você é um extrator de texto de documentos clínicos e escolares brasileiros.
Leia TODA a imagem (laudo, avaliação ou relatório) e transcreva o conteúdo relevante em português.
Extraia de forma estruturada:
- Dados do paciente/aluno (nome, data nascimento, CPF, sexo, endereço)
- Diagnóstico, CID (CID-10 e/ou CID-11)
- Médicos/especialistas e especialidades
- Observações clínicas, sintomas, evolução
- Orientações à escola / recomendações pedagógicas
- Acompanhamentos terapêuticos mencionados
- Qualquer outra informação útil para montar PEI/PAEE

Arquivo: {$originalName}
Tipo: {$label}

Responda em texto corrido estruturado (não JSON). Não omita diagnósticos, CIDs nem orientações escolares.
PROMPT;

        $response = $this->chat([
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:{$mime};base64,{$base64}",
                            'detail' => 'high',
                        ],
                    ],
                ],
            ],
        ], jsonMode: false, temperature: 0.1);

        $text = trim((string) data_get($response, 'choices.0.message.content'));

        if ($text === '') {
            throw new RuntimeException("OCR vazio para {$label} ({$originalName}).");
        }

        return $this->truncate($text, 12000);
    }

    /**
     * @param  array<string, string>  $extractedTexts
     * @return array<string, mixed>
     */
    private function callOpenAiForDocument(Student $student, DocumentType $type, array $extractedTexts): array
    {
        $schema = $type === DocumentType::Pei
            ? $this->peiSchemaDescription()
            : $this->paeeSchemaDescription();

        $studentContext = $this->studentContext($student);
        $documentsText = $this->buildDocumentsPrompt($extractedTexts);

        if (trim(strip_tags($documentsText)) === '' || $this->documentsSeemEmpty($extractedTexts)) {
            throw new RuntimeException('Nenhum conteúdo útil foi extraído dos documentos enviados.');
        }

        $system = <<<'PROMPT'
Você é um especialista em educação inclusiva no Brasil (AEE, LDB, BNCC e legislação vigente).
Analise os documentos clínicos/escolares já transcritos e redija o plano em português do Brasil.
Responda APENAS com JSON válido, sem markdown.
PRIORIZE os dados dos DOCUMENTOS ENVIADOS sobre o cadastro do sistema quando houver conflito (nome, CID, diagnóstico, especialistas, recomendações).
Preencha especialistas com base no que aparece nos laudos/relatórios.
Use diagnósticos e CIDs reais dos documentos.
Para PEI: estruture o plano nas 5 dimensões obrigatórias (Quem é a criança; Como ela aprende; O que precisa desenvolver; Como acompanhar; Como aplicar). Preencha cada campo com base nas evidências; para a 3ª dimensão gere objetivos por área com habilidade atual/esperada, prazo, indicador e forma de avaliação; para a 5ª dimensão transforme cada objetivo em plano de intervenção concreto (estratégia, materiais, momentos, adaptação, papel do professor, família, frequência).
Para PAEE (checklist de habilidades): marque cada habilidade com o nível mais coerente com as evidências dos documentos.
Se a evidência for insuficiente para uma habilidade, use "nao_observado".
Se a habilidade estiver acima da faixa etária / não aplicável, use "nao_observado".
Linguagem técnica, objetiva, no estilo de formulário oficial preenchido.
Não invente dados clínicos ausentes; se faltar texto descritivo, escreva "Não informado nos documentos enviados".
PROMPT;

        $user = <<<PROMPT
Monte um {$type->label()} completo com base nos dados abaixo, seguindo EXATAMENTE a estrutura JSON.

DADOS DO CADASTRO (podem estar incompletos — prefira os documentos):
{$studentContext}

DOCUMENTOS ENVIADOS (texto já extraído por OCR/leitura — FONTE PRINCIPAL):
{$documentsText}

ESTRUTURA JSON OBRIGATÓRIA:
{$schema}
PROMPT;

        $response = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], jsonMode: true, temperature: 0.2);

        $raw = data_get($response, 'choices.0.message.content');

        if (! is_string($raw) || $raw === '') {
            throw new RuntimeException('Resposta da IA vazia na geração do documento.');
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('JSON inválido retornado pela IA.');
        }

        return $decoded;
    }

    /**
     * @param  array<int, array{role: string, content: mixed}>  $messages
     * @return array<string, mixed>
     */
    private function chat(array $messages, bool $jsonMode = false, float $temperature = 0.2): array
    {
        $payload = [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
            ->withToken((string) config('services.openai.api_key'))
            ->timeout((int) config('services.openai.timeout', 180))
            ->withOptions($this->httpOptions())
            ->acceptJson()
            ->post('/chat/completions', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI HTTP '.$response->status().': '.$response->body());
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function httpOptions(): array
    {
        $caBundle = $this->resolveCaBundle();

        return $caBundle ? ['verify' => $caBundle] : [];
    }

    private function resolveCaBundle(): ?string
    {
        $candidates = array_filter([
            config('services.openai.ca_bundle'),
            env('OPENAI_CA_BUNDLE'),
            ini_get('curl.cainfo') ?: null,
            ini_get('openssl.cafile') ?: null,
            'F:\\laragon\\etc\\ssl\\cacert.pem',
            'D:\\laragon\\etc\\ssl\\cacert.pem',
            'C:\\laragon\\etc\\ssl\\cacert.pem',
            storage_path('certs/cacert.pem'),
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $sources
     */
    private function documentsSeemEmpty(array $sources): bool
    {
        $joined = implode("\n", $sources);
        $joined = trim(preg_replace('/\[.*?\]/u', '', $joined) ?? '');

        return mb_strlen($joined) < 40;
    }

    /**
     * @param  array<int, array{label: string, kind: string, text: string, original_name: string}>  $prepared
     * @return array<string, string>
     */
    private function sourcesPreview(array $prepared): array
    {
        $sources = [];

        foreach ($prepared as $item) {
            if ($item['kind'] === 'image') {
                $sources[$item['label']] = '[Imagem: '.$item['original_name'].' — aguardava OCR]';
            } else {
                $sources[$item['label']] = mb_substr($item['text'], 0, 5000);
            }
        }

        return $sources;
    }

    private function studentContext(Student $student): string
    {
        $student->loadMissing('turma');

        return implode("\n", [
            'Nome: '.$student->full_name,
            'Série/Turma: '.($student->turma->name ?? 'N/I'),
            'Turno: '.($student->turma->turno ?? 'N/I'),
            'Data de nascimento: '.($student->birth_date?->format('d/m/Y') ?? 'N/I'),
            'CPF: '.$student->cpf,
            'Responsável legal: '.$student->legal_guardian,
            'CID / Diagnóstico cadastrado: '.$student->cid,
            'Status do laudo médico: '.$student->medical_report_status->label(),
            'Ano de ingresso: '.$student->entry_year,
            'Observações do cadastro: '.$student->observations,
            'Data sugerida de início do PEI: '.now()->format('d/m/Y'),
            'Data sugerida de término do PEI: '.now()->addMonths(4)->format('d/m/Y'),
        ]);
    }

    /**
     * @param  array<string, string>  $sources
     */
    private function buildDocumentsPrompt(array $sources): string
    {
        $parts = [];

        foreach ($sources as $label => $text) {
            $parts[] = "### {$label}\n{$text}";
        }

        return implode("\n\n", $parts);
    }

    private function peiSchemaDescription(): string
    {
        return <<<'JSON'
{
  "titulo": "PLANEJAMENTO EDUCACIONAL INDIVIDUALIZADO (PEI)",
  "sexo": "M ou F (apenas se constar nos documentos; senão string vazia)",
  "serie": "série/ano escolar inferida da turma ou documentos",
  "turno": "turno do aluno",
  "diagnostico": "diagnóstico principal completo (ex: TEA nível 1 / Autismo; TDAH). Inclua CIDs quando houver",
  "especialistas": ["liste somente os que constarem nos documentos dentre: Neurologista, Psiquiatra, Psicólogo, Fonoaudiólogo, T.O., Psicomotricista, Nutricionista, ABA, Psicopedagogia ou Neuropsicopedagogia, Musicoterapia"],
  "outros_especialistas": "outros acompanhamentos não listados, ou string vazia",
  "sem_intervencao_terapeutica": false,
  "data_inicio": "dd/mm/aaaa (data de início sugerida do PEI)",
  "data_termino": "dd/mm/aaaa (data de término sugerida)",
  "tempo_previsto_bimestral": "ex: 2 bimestres",
  "dimensao_1": {
    "objetivo": "Conhecer a criança além do diagnóstico.",
    "pergunta_central": "Quem é essa criança e quais fatores influenciam sua aprendizagem?",
    "identificacao": "dados de identificação e perfil geral da criança",
    "historico_desenvolvimento": "marcos e histórico do desenvolvimento",
    "diagnostico_laudos": "síntese do diagnóstico e laudos (CID, nível de suporte, etc.)",
    "rede_atendimento": "rede de atendimento (escola, terapeutas, serviços)",
    "contexto_familiar": "contexto familiar e rede de apoio",
    "interesses_preferencias": "interesses, preferências e motivadores",
    "potencialidades": "potencialidades e pontos fortes",
    "desafios": "desafios e necessidades de apoio",
    "perfil_sensorial": "perfil sensorial (hipo/hipersensibilidades, autorregulação)",
    "comunicacao": "formas de comunicação utilizadas",
    "saude": "aspectos de saúde relevantes para a escola",
    "medicamentos": "medicamentos em uso, se houver",
    "rotina": "rotina diária e escolar"
  },
  "dimensao_2": {
    "objetivo": "Compreender a melhor forma de ensinar.",
    "pergunta_central": "O que favorece e o que dificulta sua aprendizagem?",
    "estilo_aprendizagem": "estilo de aprendizagem preferencial",
    "atencao": "atenção e engajamento",
    "motivacao": "fatores motivacionais",
    "comunicacao": "comunicação no contexto de aprendizagem",
    "linguagem": "linguagem receptiva/expressiva",
    "socializacao": "interação social e pares",
    "brincadeiras": "brincadeiras e engajamento lúdico",
    "cognicao": "aspectos cognitivos relevantes",
    "funcoes_executivas": "funções executivas (planejamento, inibição, flexibilidade)",
    "motricidade": "motricidade grossa e fina no contexto escolar",
    "autonomia": "autonomia e independência",
    "barreiras_aprendizagem": "barreiras que dificultam a aprendizagem",
    "recursos_facilitadores": "recursos e estratégias que facilitam a aprendizagem"
  },
  "dimensao_3": {
    "objetivo": "Definir metas claras por área de desenvolvimento.",
    "pergunta_central": "Quais habilidades queremos desenvolver durante este período?",
    "objetivos": [
      {
        "area": "uma de: Desenvolvimento Cognitivo | Linguagem | Comunicação | Motricidade Grossa | Motricidade Fina | Autonomia | Socialização | Regulação Emocional | Campos de Experiência da BNCC",
        "habilidade_atual": "o que a criança já realiza",
        "habilidade_esperada": "meta a desenvolver no período",
        "prazo": "ex: 1 bimestre / 2 meses",
        "indicador_sucesso": "como evidenciar o avanço",
        "forma_avaliacao": "como será avaliado (observação, checklist, etc.)"
      }
    ]
  },
  "dimensao_4": {
    "objetivo": "Monitorar o progresso continuamente.",
    "pergunta_central": "Como saberemos que a criança está evoluindo?",
    "instrumentos": {
      "checklists": "como e quando usar checklists",
      "rubricas": "rubricas de avaliação, se aplicável",
      "registros_fotograficos": "uso de registros fotográficos",
      "videos": "uso de vídeos para acompanhamento",
      "portfolio": "organização do portfólio",
      "relatorios": "relatórios periódicos",
      "escalas_desenvolvimento": "escalas de desenvolvimento utilizadas",
      "reunioes_familia": "frequência e foco das reuniões com a família",
      "revisoes_pei": "quando e como o PEI será revisado"
    },
    "o_que_evoluiu": "registrar o que evoluiu (preencher com base nos documentos ou deixar orientação para acompanhamento)",
    "o_que_nao_evoluiu": "registrar o que ainda não evoluiu",
    "estrategias_funcionaram": "estratégias que funcionaram",
    "o_que_modificar": "o que precisa ser modificado no plano"
  },
  "dimensao_5": {
    "objetivo": "Transformar cada objetivo em ações concretas (Plano de Intervenção Pedagógica).",
    "pergunta_central": "Como aplicar o plano no dia a dia?",
    "planos": [
      {
        "objetivo": "objetivo vinculado à dimensão 3 (ex: Esperar a vez durante jogos)",
        "o_que_sera_ensinado": "habilidade concreta a ensinar",
        "como_ensinar": "estratégias (modelagem, demonstração, pares, histórias sociais, rotina visual, reforço positivo, etc.)",
        "em_quais_momentos": "momentos da rotina (roda, parque, alimentação, artes, etc.)",
        "materiais": "materiais e recursos",
        "adaptacao": "adaptações necessárias",
        "papel_professor": "papel do professor/mediador",
        "envolvimento_familia": "como a família participa",
        "frequencia": "frequência da intervenção",
        "tempo": "duração típica da atividade",
        "avaliacao": "como avaliar o progresso desta intervenção"
      }
    ]
  },
  "obs_direcao": "",
  "obs_familiares": "",
  "obs_equipe_multidisciplinar": ""
}
JSON;
    }

    private function paeeSchemaDescription(): string
    {
        $skills = '';
        foreach (PaeeSkills::all() as $number => $label) {
            $skills .= "    \"{$number}\": \"realiza_sem_suporte|realiza_com_ajuda|nao_realiza|nao_observado — {$label}\",\n";
        }

        return <<<JSON
{
  "titulo": "PAEE (PLANO DE ATENDIMENTO EDUCACIONAL ESPECIALIZADO)",
  "sexo": "M ou F (se constar nos documentos; senão string vazia)",
  "serie": "série/ano escolar",
  "turno": "turno",
  "diagnostico": "diagnóstico principal com CIDs quando houver",
  "especialistas": ["Neurologista", "Psiquiatra", "Psicólogo", "Fonoaudiólogo", "T.O.", "Psicomotricista", "Nutricionista", "ABA", "Psicopedagogia ou Neuropsicopedagogia", "Musicoterapia"],
  "outros_especialistas": "",
  "avaliacao_periodo_inicio": "dd/mm/aaaa",
  "avaliacao_periodo_fim": "dd/mm/aaaa",
  "observacoes_periodo": "texto curto sobre o período observado",
  "resumo_areas": {
    "linguagem_nao_verbal": "desenvolveu|desenvolveu_com_ajuda|nao_desenvolveu|as_vezes",
    "linguagem_verbal": "desenvolveu|desenvolveu_com_ajuda|nao_desenvolveu|as_vezes",
    "conteudo_programatico": "desenvolveu|desenvolveu_com_ajuda|nao_desenvolveu|as_vezes",
    "aspecto_social": "desenvolveu|desenvolveu_com_ajuda|nao_desenvolveu|as_vezes",
    "aspecto_motor": "desenvolveu|desenvolveu_com_ajuda|nao_desenvolveu|as_vezes"
  },
  "habilidades": {
{$skills}    "observa_idade": true
  },
  "material_didatico": "materiais utilizados pelo aluno",
  "solicitacoes_familia": "solicitações à família",
  "encaminhamento": "encaminhamentos (ex: neurologia, fono, psicopedagogia)",
  "observacoes_gerais": "observações gerais do PAEE"
}
JSON;
    }

    /**
     * @param  array<string, string>  $sources
     * @return array<string, mixed>
     */
    private function fallbackContent(Student $student, DocumentType $type, array $sources, string $reason = ''): array
    {
        $snippets = [];
        foreach ($sources as $label => $text) {
            if (str_starts_with($text, '[')) {
                continue;
            }
            $snippets[] = $label.': '.mb_substr(preg_replace('/\s+/', ' ', $text) ?? '', 0, 400);
        }

        $excerpt = implode("\n", $snippets) ?: ($reason !== ''
            ? $reason
            : 'Conteúdo extraído indisponível.');

        if ($type === DocumentType::Pei) {
            $student->loadMissing('turma');
            $naoInformado = 'Não informado nos documentos enviados.';

            return [
                'titulo' => 'PLANEJAMENTO EDUCACIONAL INDIVIDUALIZADO (PEI)',
                'sexo' => '',
                'serie' => $student->turma->name ?? '',
                'turno' => $student->turma->turno ?? '',
                'diagnostico' => $student->cid,
                'especialistas' => [],
                'outros_especialistas' => '',
                'sem_intervencao_terapeutica' => false,
                'data_inicio' => now()->format('d/m/Y'),
                'data_termino' => now()->addMonths(4)->format('d/m/Y'),
                'tempo_previsto_bimestral' => '2 bimestres',
                'dimensao_1' => [
                    'objetivo' => 'Conhecer a criança além do diagnóstico.',
                    'pergunta_central' => 'Quem é essa criança e quais fatores influenciam sua aprendizagem?',
                    'identificacao' => $student->full_name.' — '.$student->turma->name,
                    'historico_desenvolvimento' => $excerpt !== '' ? mb_substr($excerpt, 0, 500) : $naoInformado,
                    'diagnostico_laudos' => $student->cid ?: $naoInformado,
                    'rede_atendimento' => $naoInformado,
                    'contexto_familiar' => 'Responsável: '.$student->legal_guardian,
                    'interesses_preferencias' => $naoInformado,
                    'potencialidades' => $naoInformado,
                    'desafios' => $student->observations ?: $naoInformado,
                    'perfil_sensorial' => $naoInformado,
                    'comunicacao' => $naoInformado,
                    'saude' => $naoInformado,
                    'medicamentos' => $naoInformado,
                    'rotina' => $naoInformado,
                ],
                'dimensao_2' => [
                    'objetivo' => 'Compreender a melhor forma de ensinar.',
                    'pergunta_central' => 'O que favorece e o que dificulta sua aprendizagem?',
                    'estilo_aprendizagem' => $naoInformado,
                    'atencao' => $naoInformado,
                    'motivacao' => $naoInformado,
                    'comunicacao' => $naoInformado,
                    'linguagem' => $naoInformado,
                    'socializacao' => $naoInformado,
                    'brincadeiras' => $naoInformado,
                    'cognicao' => $naoInformado,
                    'funcoes_executivas' => $naoInformado,
                    'motricidade' => $naoInformado,
                    'autonomia' => $naoInformado,
                    'barreiras_aprendizagem' => $naoInformado,
                    'recursos_facilitadores' => 'Recursos visuais, materiais adaptados e mediação individualizada.',
                ],
                'dimensao_3' => [
                    'objetivo' => 'Definir metas claras por área de desenvolvimento.',
                    'pergunta_central' => 'Quais habilidades queremos desenvolver durante este período?',
                    'objetivos' => [
                        [
                            'area' => 'Autonomia',
                            'habilidade_atual' => $naoInformado,
                            'habilidade_esperada' => 'Definir meta a partir das necessidades identificadas nos documentos.',
                            'prazo' => '2 bimestres',
                            'indicador_sucesso' => 'Observação registrada em checklist de acompanhamento.',
                            'forma_avaliacao' => 'Observação em sala e registro periódico.',
                        ],
                        [
                            'area' => 'Socialização',
                            'habilidade_atual' => $naoInformado,
                            'habilidade_esperada' => 'Ampliar participação em interações com pares.',
                            'prazo' => '2 bimestres',
                            'indicador_sucesso' => 'Participação observada em atividades em grupo.',
                            'forma_avaliacao' => 'Registro anecdótico e feedback da família.',
                        ],
                    ],
                ],
                'dimensao_4' => [
                    'objetivo' => 'Monitorar o progresso continuamente.',
                    'pergunta_central' => 'Como saberemos que a criança está evoluindo?',
                    'instrumentos' => [
                        'checklists' => 'Checklist semanal das metas prioritárias.',
                        'rubricas' => $naoInformado,
                        'registros_fotograficos' => 'Com autorização da família, quando pertinente.',
                        'videos' => $naoInformado,
                        'portfolio' => 'Portfólio com evidências das atividades.',
                        'relatorios' => 'Relatórios bimestrais de progresso.',
                        'escalas_desenvolvimento' => $naoInformado,
                        'reunioes_familia' => 'Reuniões periódicas com a família.',
                        'revisoes_pei' => 'Revisão ao final do período previsto.',
                    ],
                    'o_que_evoluiu' => 'A preencher ao longo do acompanhamento.',
                    'o_que_nao_evoluiu' => 'A preencher ao longo do acompanhamento.',
                    'estrategias_funcionaram' => 'A preencher ao longo do acompanhamento.',
                    'o_que_modificar' => 'Revisar metas e estratégias conforme evidências.',
                ],
                'dimensao_5' => [
                    'objetivo' => 'Transformar cada objetivo em ações concretas (Plano de Intervenção Pedagógica).',
                    'pergunta_central' => 'Como aplicar o plano no dia a dia?',
                    'planos' => [
                        [
                            'objetivo' => 'Ampliar autonomia e participação social.',
                            'o_que_sera_ensinado' => 'Habilidades prioritárias identificadas nos documentos.',
                            'como_ensinar' => 'Modelagem, demonstração, rotina visual e reforço positivo.',
                            'em_quais_momentos' => 'Roda de conversa, atividades livres e momentos de rotina.',
                            'materiais' => 'Cartões visuais, materiais sensoriais e jogos.',
                            'adaptacao' => 'Instruções em etapas e mais tempo para resposta.',
                            'papel_professor' => 'Mediar, modelar, incentivar e registrar observações.',
                            'envolvimento_familia' => 'Repetir a habilidade em casa e manter comunicação constante.',
                            'frequencia' => 'Diariamente',
                            'tempo' => '15 a 20 minutos',
                            'avaliacao' => 'Registrar avanços em checklist e reuniões de acompanhamento.',
                        ],
                    ],
                ],
                'obs_direcao' => '',
                'obs_familiares' => '',
                'obs_equipe_multidisciplinar' => '',
                '_fallback' => true,
                '_fallback_reason' => $reason,
            ];
        }

        $student->loadMissing('turma');

        $habilidades = [];
        foreach (array_keys(PaeeSkills::all()) as $number) {
            $habilidades[(string) $number] = 'nao_observado';
        }

        return [
            'titulo' => 'PAEE (PLANO DE ATENDIMENTO EDUCACIONAL ESPECIALIZADO)',
            'sexo' => '',
            'serie' => $student->turma->name ?? '',
            'turno' => $student->turma->turno ?? '',
            'diagnostico' => $student->cid,
            'especialistas' => [],
            'outros_especialistas' => '',
            'avaliacao_periodo_inicio' => now()->startOfMonth()->format('d/m/Y'),
            'avaliacao_periodo_fim' => now()->format('d/m/Y'),
            'observacoes_periodo' => $excerpt !== '' ? mb_substr($excerpt, 0, 500) : $student->observations,
            'resumo_areas' => [
                'linguagem_nao_verbal' => 'as_vezes',
                'linguagem_verbal' => 'as_vezes',
                'conteudo_programatico' => 'as_vezes',
                'aspecto_social' => 'as_vezes',
                'aspecto_motor' => 'as_vezes',
            ],
            'habilidades' => $habilidades,
            'material_didatico' => 'Materiais adaptados e recursos visuais conforme necessidade.',
            'solicitacoes_familia' => 'Manter rotina de acompanhamento e reforçar estratégias em casa.',
            'encaminhamento' => 'Avaliar necessidade de acompanhamento multidisciplinar conforme laudos.',
            'observacoes_gerais' => $reason !== '' ? $reason : 'Preenchimento parcial — regenere com a IA a partir dos documentos enviados.',
            '_fallback' => true,
            '_fallback_reason' => $reason,
        ];
    }

    /**
     * @param  array<string, string>  $sources
     * @return array<string, string>
     */
    private function sanitizeSourcesForStorage(array $sources): array
    {
        $clean = [];

        foreach ($sources as $label => $text) {
            $clean[$label] = mb_substr($text, 0, 8000);
        }

        return $clean;
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max)."\n\n[Texto truncado]";
    }
}
