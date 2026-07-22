<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\PaeeSkills;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class DocumentGeneratorService
{
    public function __construct(
        private DocumentAiService $aiService
    ) {}

    /**
     * @param  array<int, array{type: string, path: string, original_name: string}>  $attachments
     */
    public function generate(
        Student $student,
        DocumentType $type,
        string $format,
        User $creator,
        array $attachments = []
    ): GeneratedDocument {
        $student->loadMissing(['school', 'turma']);

        $aiResult = $this->aiService->generateDocumentContent($student, $type, $attachments);
        $aiContent = $aiResult['content'];
        $sources = $aiResult['sources'];

        $content = $this->buildContent($student, $student->school, $type, $aiContent);

        $filename = sprintf(
            '%s_%s_%s.%s',
            $type->value,
            str($student->full_name)->slug(),
            now()->format('YmdHis'),
            $format === 'pdf' ? 'pdf' : 'docx'
        );

        $directory = "documents/{$student->school_id}/{$student->id}";
        $path = "{$directory}/{$filename}";

        if ($format === 'pdf') {
            $this->generatePdf($content, $path);
        } else {
            $this->generateWord($content, $path);
        }

        $status = $creator->isDirecao()
            ? DocumentStatus::Aprovado
            : DocumentStatus::Pendente;

        return GeneratedDocument::create([
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'created_by' => $creator->id,
            'approved_by' => $creator->isDirecao() ? $creator->id : null,
            'type' => $type,
            'format' => $format,
            'file_path' => $path,
            'ai_content' => $aiContent,
            'extracted_sources' => $sources,
            'status' => $status,
            'approved_at' => $creator->isDirecao() ? now() : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $aiContent
     * @return array<string, mixed>
     */
    private function buildContent(Student $student, School $school, DocumentType $type, array $aiContent): array
    {
        return [
            'school' => $school,
            'student' => $student,
            'type' => $type,
            'ai' => $aiContent,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function generatePdf(array $content, string $path): void
    {
        $view = $content['type'] === DocumentType::Paee
            ? 'documents.paee'
            : 'documents.pei';

        $pdf = Pdf::loadView($view, $content)->setPaper('a4');
        Storage::disk('local')->put($path, $pdf->output());
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function generateWord(array $content, string $path): void
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $ai = $content['ai'];
        $student = $content['student'];
        $type = $content['type'];

        if ($content['school']->logo_path && Storage::disk('public')->exists($content['school']->logo_path)) {
            $section->addImage(Storage::disk('public')->path($content['school']->logo_path), [
                'width' => 80,
                'height' => 80,
                'alignment' => Jc::CENTER,
            ]);
        }

        $section->addText($content['school']->name, ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();
        $section->addText($ai['titulo'] ?? ($type === DocumentType::Pei ? 'PLANEJAMENTO EDUCACIONAL INDIVIDUALIZADO (PEI)' : $type->label()), ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        if ($type === DocumentType::Pei) {
            $this->addWordPei($section, $content);
        } else {
            $this->addWordPaee($section, $content);
        }

        $section->addTextBreak();
        $section->addText('Documento gerado em: '.$content['generated_at'].' — PLANNIA (com apoio de IA)', ['italic' => true, 'size' => 9]);

        $tempPath = Storage::disk('local')->path('temp_'.uniqid().'.docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
        Storage::disk('local')->put($path, file_get_contents($tempPath));
        @unlink($tempPath);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function addWordPei(mixed $section, array $content): void
    {
        $ai = $content['ai'];
        $student = $content['student'];
        $sexo = strtoupper((string) ($ai['sexo'] ?? ''));
        $especialistas = implode(', ', $ai['especialistas'] ?? []) ?: 'Não informado';
        $txt = fn (?string $value) => ($value !== null && trim($value) !== '') ? $value : 'Não informado nos documentos enviados.';

        $d1 = $ai['dimensao_1'] ?? [];
        $d2 = $ai['dimensao_2'] ?? [];
        $d3 = $ai['dimensao_3'] ?? [];
        $d4 = $ai['dimensao_4'] ?? [];
        $d5 = $ai['dimensao_5'] ?? [];
        $instrumentos = $d4['instrumentos'] ?? [];

        $section->addText('INFORMAÇÕES DO ALUNO', ['bold' => true, 'size' => 12]);
        $section->addText('Nome completo do aluno: '.$student->full_name);
        $section->addText('Data de nascimento: '.($student->birth_date?->format('d/m/Y') ?? 'N/I'));
        $section->addText('Sexo: '.($sexo !== '' ? $sexo : 'N/I').' | Série: '.($ai['serie'] ?? $student->turma->name).' | Turno: '.($ai['turno'] ?? $student->turma->turno));
        $section->addText('Diagnóstico: '.($ai['diagnostico'] ?? $student->cid));
        $section->addText('Acompanhamento com especialistas: '.$especialistas);
        if (! empty($ai['outros_especialistas'])) {
            $section->addText('Outros: '.$ai['outros_especialistas']);
        }
        $section->addText('Data de início: '.($ai['data_inicio'] ?? 'N/I').' | Data de término: '.($ai['data_termino'] ?? 'N/I').' | Tempo previsto: '.($ai['tempo_previsto_bimestral'] ?? 'N/I'));
        $section->addTextBreak();

        $section->addText('1ª DIMENSÃO — QUEM É A CRIANÇA?', ['bold' => true, 'size' => 12]);
        $section->addText('Objetivo: '.$txt($d1['objetivo'] ?? 'Conhecer a criança além do diagnóstico.'), ['italic' => true, 'size' => 9]);
        $section->addText('Pergunta central: '.$txt($d1['pergunta_central'] ?? 'Quem é essa criança e quais fatores influenciam sua aprendizagem?'), ['italic' => true, 'size' => 9]);
        $this->addWordSection($section, 'Identificação', $d1['identificacao'] ?? '');
        $this->addWordSection($section, 'Histórico do desenvolvimento', $d1['historico_desenvolvimento'] ?? '');
        $this->addWordSection($section, 'Diagnóstico e laudos', $d1['diagnostico_laudos'] ?? '');
        $this->addWordSection($section, 'Rede de atendimento', $d1['rede_atendimento'] ?? '');
        $this->addWordSection($section, 'Contexto familiar', $d1['contexto_familiar'] ?? '');
        $this->addWordSection($section, 'Interesses e preferências', $d1['interesses_preferencias'] ?? '');
        $this->addWordSection($section, 'Potencialidades', $d1['potencialidades'] ?? '');
        $this->addWordSection($section, 'Desafios', $d1['desafios'] ?? '');
        $this->addWordSection($section, 'Perfil sensorial', $d1['perfil_sensorial'] ?? '');
        $this->addWordSection($section, 'Comunicação', $d1['comunicacao'] ?? '');
        $this->addWordSection($section, 'Saúde', $d1['saude'] ?? '');
        $this->addWordSection($section, 'Medicamentos', $d1['medicamentos'] ?? '');
        $this->addWordSection($section, 'Rotina', $d1['rotina'] ?? '');

        $section->addText('2ª DIMENSÃO — COMO ELA APRENDE?', ['bold' => true, 'size' => 12]);
        $section->addText('Objetivo: '.$txt($d2['objetivo'] ?? 'Compreender a melhor forma de ensinar.'), ['italic' => true, 'size' => 9]);
        $section->addText('Pergunta central: '.$txt($d2['pergunta_central'] ?? 'O que favorece e o que dificulta sua aprendizagem?'), ['italic' => true, 'size' => 9]);
        $this->addWordSection($section, 'Estilo de aprendizagem', $d2['estilo_aprendizagem'] ?? '');
        $this->addWordSection($section, 'Atenção', $d2['atencao'] ?? '');
        $this->addWordSection($section, 'Motivação', $d2['motivacao'] ?? '');
        $this->addWordSection($section, 'Comunicação', $d2['comunicacao'] ?? '');
        $this->addWordSection($section, 'Linguagem', $d2['linguagem'] ?? '');
        $this->addWordSection($section, 'Socialização', $d2['socializacao'] ?? '');
        $this->addWordSection($section, 'Brincadeiras', $d2['brincadeiras'] ?? '');
        $this->addWordSection($section, 'Cognição', $d2['cognicao'] ?? '');
        $this->addWordSection($section, 'Funções executivas', $d2['funcoes_executivas'] ?? '');
        $this->addWordSection($section, 'Motricidade', $d2['motricidade'] ?? '');
        $this->addWordSection($section, 'Autonomia', $d2['autonomia'] ?? '');
        $this->addWordSection($section, 'Barreiras para aprendizagem', $d2['barreiras_aprendizagem'] ?? '');
        $this->addWordSection($section, 'Recursos que facilitam', $d2['recursos_facilitadores'] ?? '');

        $section->addText('3ª DIMENSÃO — O QUE ELA PRECISA DESENVOLVER?', ['bold' => true, 'size' => 12]);
        $section->addText('Objetivo: '.$txt($d3['objetivo'] ?? 'Definir metas claras.'), ['italic' => true, 'size' => 9]);
        $section->addText('Pergunta central: '.$txt($d3['pergunta_central'] ?? 'Quais habilidades queremos desenvolver durante este período?'), ['italic' => true, 'size' => 9]);
        $section->addTextBreak();

        $objetivos = $d3['objetivos'] ?? [];
        if ($objetivos === []) {
            $section->addText('Nenhum objetivo informado.');
            $section->addTextBreak();
        } else {
            foreach ($objetivos as $index => $obj) {
                $section->addText('Objetivo '.($index + 1).' — '.($obj['area'] ?? 'Área'), ['bold' => true, 'size' => 11]);
                $section->addText('Habilidade atual: '.$txt($obj['habilidade_atual'] ?? null));
                $section->addText('Habilidade esperada: '.$txt($obj['habilidade_esperada'] ?? null));
                $section->addText('Prazo: '.$txt($obj['prazo'] ?? null));
                $section->addText('Indicador de sucesso: '.$txt($obj['indicador_sucesso'] ?? null));
                $section->addText('Forma de avaliação: '.$txt($obj['forma_avaliacao'] ?? null));
                $section->addTextBreak();
            }
        }

        $section->addText('4ª DIMENSÃO — COMO ACOMPANHAR A EVOLUÇÃO?', ['bold' => true, 'size' => 12]);
        $section->addText('Objetivo: '.$txt($d4['objetivo'] ?? 'Monitorar o progresso continuamente.'), ['italic' => true, 'size' => 9]);
        $section->addText('Pergunta central: '.$txt($d4['pergunta_central'] ?? 'Como saberemos que a criança está evoluindo?'), ['italic' => true, 'size' => 9]);
        $this->addWordSection($section, 'Checklists', $instrumentos['checklists'] ?? '');
        $this->addWordSection($section, 'Rubricas', $instrumentos['rubricas'] ?? '');
        $this->addWordSection($section, 'Registros fotográficos', $instrumentos['registros_fotograficos'] ?? '');
        $this->addWordSection($section, 'Vídeos', $instrumentos['videos'] ?? '');
        $this->addWordSection($section, 'Portfólio', $instrumentos['portfolio'] ?? '');
        $this->addWordSection($section, 'Relatórios', $instrumentos['relatorios'] ?? '');
        $this->addWordSection($section, 'Escalas de desenvolvimento', $instrumentos['escalas_desenvolvimento'] ?? '');
        $this->addWordSection($section, 'Reuniões com a família', $instrumentos['reunioes_familia'] ?? '');
        $this->addWordSection($section, 'Revisões do PEI', $instrumentos['revisoes_pei'] ?? '');
        $this->addWordSection($section, 'O que evoluiu?', $d4['o_que_evoluiu'] ?? '');
        $this->addWordSection($section, 'O que não evoluiu?', $d4['o_que_nao_evoluiu'] ?? '');
        $this->addWordSection($section, 'Quais estratégias funcionaram?', $d4['estrategias_funcionaram'] ?? '');
        $this->addWordSection($section, 'O que precisa ser modificado?', $d4['o_que_modificar'] ?? '');

        $section->addText('5ª DIMENSÃO — COMO APLICAR? (PLANO DE INTERVENÇÃO PEDAGÓGICA)', ['bold' => true, 'size' => 12]);
        $section->addText('Objetivo: '.$txt($d5['objetivo'] ?? 'Transformar cada objetivo em ações concretas.'), ['italic' => true, 'size' => 9]);
        $section->addText('Pergunta central: '.$txt($d5['pergunta_central'] ?? 'Como aplicar o plano no dia a dia?'), ['italic' => true, 'size' => 9]);
        $section->addTextBreak();

        $planos = $d5['planos'] ?? [];
        if ($planos === []) {
            $section->addText('Nenhum plano de intervenção informado.');
            $section->addTextBreak();
        } else {
            foreach ($planos as $index => $plano) {
                $section->addText('Intervenção '.($index + 1).' — '.$txt($plano['objetivo'] ?? null), ['bold' => true, 'size' => 11]);
                $section->addText('O que será ensinado: '.$txt($plano['o_que_sera_ensinado'] ?? null));
                $section->addText('Como ensinar: '.$txt($plano['como_ensinar'] ?? null));
                $section->addText('Em quais momentos: '.$txt($plano['em_quais_momentos'] ?? null));
                $section->addText('Materiais: '.$txt($plano['materiais'] ?? null));
                $section->addText('Adaptação: '.$txt($plano['adaptacao'] ?? null));
                $section->addText('Papel do professor: '.$txt($plano['papel_professor'] ?? null));
                $section->addText('Envolvimento da família: '.$txt($plano['envolvimento_familia'] ?? null));
                $section->addText('Frequência: '.$txt($plano['frequencia'] ?? null).(! empty($plano['tempo']) ? ' | Tempo: '.$plano['tempo'] : ''));
                $section->addText('Avaliação: '.$txt($plano['avaliacao'] ?? null));
                $section->addTextBreak();
            }
        }

        $section->addText('LEITURA DO PEI PELOS PROFISSIONAIS E EQUIPE MULTIDISCIPLINAR', ['bold' => true, 'size' => 12]);
        $this->addWordSection($section, 'Direção/Coordenação escolar', $ai['obs_direcao'] ?? '');
        $this->addWordSection($section, 'Familiares e/ou responsáveis', $ai['obs_familiares'] ?? '');
        $this->addWordSection($section, 'Equipe multidisciplinar', $ai['obs_equipe_multidisciplinar'] ?? '');

        $section->addText('Assinatura do professor: ________________________________');
        $section->addText('Assinatura da coordenação/direção: ________________________________');
        $section->addText('Assinatura do responsável: ________________________________');
        $section->addText('Data: ____/____/________');
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function addWordPaee(mixed $section, array $content): void
    {
        $ai = $content['ai'];
        $student = $content['student'];
        $sexo = strtoupper((string) ($ai['sexo'] ?? ''));
        $especialistas = implode(', ', $ai['especialistas'] ?? []) ?: 'Não informado';
        $resumo = $ai['resumo_areas'] ?? [];
        $habilidades = $ai['habilidades'] ?? [];
        $areaLevels = PaeeSkills::areaLevels();
        $skillLevels = PaeeSkills::skillLevels();

        $section->addText('I – INFORMAÇÕES DO ALUNO', ['bold' => true, 'size' => 12]);
        $section->addText('Nome completo do aluno: '.$student->full_name);
        $section->addText('Data de nascimento: '.($student->birth_date?->format('d/m/Y') ?? 'N/I'));
        $section->addText('Sexo: '.($sexo !== '' ? $sexo : 'N/I').' | Série: '.($ai['serie'] ?? $student->turma->name).' | Turno: '.($ai['turno'] ?? $student->turma->turno));
        $section->addText('Diagnóstico: '.($ai['diagnostico'] ?? $student->cid));
        $section->addText('Acompanhamento com especialistas: '.$especialistas);
        $section->addText('Avaliação de observação do período: '.($ai['avaliacao_periodo_inicio'] ?? 'N/I').' à '.($ai['avaliacao_periodo_fim'] ?? 'N/I'));
        $this->addWordSection($section, 'Observações do período', $ai['observacoes_periodo'] ?? '');

        $section->addText('RESUMO POR ÁREAS', ['bold' => true, 'size' => 12]);
        foreach (PaeeSkills::areas() as $key => $label) {
            $level = $resumo[$key] ?? 'nao_observado';
            $section->addText($label.': '.($areaLevels[$level] ?? $level));
        }
        $section->addTextBreak();

        foreach (PaeeSkills::groups() as $group => $skills) {
            $section->addText($group, ['bold' => true, 'size' => 11]);
            foreach ($skills as $number => $label) {
                $level = $habilidades[(string) $number] ?? $habilidades[$number] ?? 'nao_observado';
                $section->addText($number.' – '.$label.' → '.($skillLevels[$level] ?? $level), ['size' => 9]);
            }
            $section->addTextBreak();
        }

        $this->addWordSection($section, 'Material didático utilizado pelo aluno', $ai['material_didatico'] ?? '');
        $this->addWordSection($section, 'Solicitações à família', $ai['solicitacoes_familia'] ?? '');
        $this->addWordSection($section, 'Encaminhamento', $ai['encaminhamento'] ?? '');
        $this->addWordSection($section, 'Observações gerais', $ai['observacoes_gerais'] ?? '');

        $section->addText('Assinatura do professor: ________________________________');
        $section->addText('Assinatura da coordenação: ________________________________');
        $section->addText('Assinatura da direção: ________________________________');
        $section->addText('Assinatura do responsável: ________________________________');
    }

    private function addWordSection(mixed $section, string $title, string $text): void
    {
        $section->addText($title, ['bold' => true, 'size' => 12]);
        $section->addText($text !== '' ? $text : 'Não informado.');
        $section->addTextBreak();
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function addWordList(mixed $section, string $title, array $items): void
    {
        $section->addText($title, ['bold' => true, 'size' => 12]);

        if ($items === []) {
            $section->addText('Não informado.');
        } else {
            foreach ($items as $item) {
                if (is_string($item) && $item !== '') {
                    $section->addListItem($item, 0);
                }
            }
        }

        $section->addTextBreak();
    }
}
