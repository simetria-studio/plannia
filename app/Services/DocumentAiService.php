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
        $prepared = $this->extractor->prepareAttachments($attachments);

        if (! config('services.openai.api_key')) {
            $sources = $this->sourcesPreview($prepared);

            return [
                'content' => $this->fallbackContent($student, $type, $sources, 'Chave OPENAI_API_KEY não configurada no .env.'),
                'sources' => $sources,
            ];
        }

        try {
            // 1) OCR / leitura dos documentos (principalmente imagens)
            $extractedTexts = $this->extractTextsWithAi($prepared);

            // 2) Geração do PEI/PAEE com o texto já extraído
            $content = $this->callOpenAiForDocument($student, $type, $extractedTexts);

            return [
                'content' => $content,
                'sources' => $this->sanitizeSourcesForStorage($extractedTexts),
            ];
        } catch (\Throwable $e) {
            Log::error('Falha na geração IA do documento', [
                'student_id' => $student->id,
                'type' => $type->value,
                'error' => $e->getMessage(),
            ]);

            $sources = $this->sourcesPreview($prepared);

            return [
                'content' => $this->fallbackContent($student, $type, $sources, 'Falha na chamada à IA: '.$e->getMessage()),
                'sources' => $sources,
            ];
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
  "processo_ensino_aprendizagem": "texto corrido com habilidades gerais e expectativas de aprendizagem individuais baseadas nos documentos",
  "conteudos_objetivos_habilidades": "o que o aluno poderá aprender neste período e atividades possíveis, baseado nos documentos",
  "recursos_ensino": "recursos a serem utilizados no processo de ensino-aprendizagem",
  "tecnologias_estrategias_adaptacoes": "tecnologias de apoio, estratégias pedagógicas e adaptações (inclua orientações do laudo à escola)",
  "espacos_profissionais": "espaços e profissionais envolvidos na aplicação do PEI",
  "criterios_instrumentos_avaliacao": "critérios e instrumentos para avaliação deste PEI",
  "mudancas_proximo_pei": "mudanças a serem consideradas para elaboração do próximo PEI",
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
                'processo_ensino_aprendizagem' => $excerpt !== '' ? $excerpt : $student->observations,
                'conteudos_objetivos_habilidades' => 'Definir conteúdos e objetivos a partir das necessidades identificadas nos documentos enviados.',
                'recursos_ensino' => 'Recursos visuais, materiais adaptados e mediação individualizada.',
                'tecnologias_estrategias_adaptacoes' => "Pictogramas e cartões visuais\nQuadros de rotina visual\nAdaptação de conteúdos com imagens e jogos educativos",
                'espacos_profissionais' => 'Salas de aula, áreas externas da escola e ambiente familiar. Profissionais: equipe pedagógica, familiares e terapeutas.',
                'criterios_instrumentos_avaliacao' => 'Relatórios de progresso, observação em sala, feedback dos responsáveis e reuniões de acompanhamento.',
                'mudancas_proximo_pei' => 'Revisão das metas e objetivos, integração de novas estratégias, colaboração multidisciplinar e participação dos responsáveis.',
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
