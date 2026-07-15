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

        $section->addText('I – INFORMAÇÕES DO ALUNO', ['bold' => true, 'size' => 12]);
        $section->addText('Nome completo do aluno: '.$student->full_name);
        $section->addText('Data de nascimento: '.($student->birth_date?->format('d/m/Y') ?? 'N/I'));
        $section->addText('Sexo: '.($sexo !== '' ? $sexo : 'N/I').' | Série: '.($ai['serie'] ?? $student->turma->name).' | Turno: '.($ai['turno'] ?? $student->turma->turno));
        $section->addText('Diagnóstico: '.($ai['diagnostico'] ?? $student->cid));
        $section->addText('Acompanhamento com especialistas: '.$especialistas);
        if (! empty($ai['outros_especialistas'])) {
            $section->addText('Outros: '.$ai['outros_especialistas']);
        }
        $section->addTextBreak();

        $section->addText('II – AÇÕES NECESSÁRIAS PARA IMPLEMENTAÇÃO DO PEI', ['bold' => true, 'size' => 12]);
        $section->addText('Data de início: '.($ai['data_inicio'] ?? 'N/I').' | Data de término: '.($ai['data_termino'] ?? 'N/I').' | Tempo previsto bimestral: '.($ai['tempo_previsto_bimestral'] ?? 'N/I'));
        $section->addTextBreak();

        $this->addWordSection($section, 'Processo de ensino e aprendizagem', $ai['processo_ensino_aprendizagem'] ?? '');
        $this->addWordSection($section, 'Conteúdos, objetivos e habilidades', $ai['conteudos_objetivos_habilidades'] ?? '');
        $this->addWordSection($section, 'Recursos de ensino-aprendizagem', $ai['recursos_ensino'] ?? '');
        $this->addWordSection($section, 'Tecnologias, estratégias e adaptações', $ai['tecnologias_estrategias_adaptacoes'] ?? '');
        $this->addWordSection($section, 'Espaços e profissionais envolvidos', $ai['espacos_profissionais'] ?? '');
        $this->addWordSection($section, 'Critérios e instrumentos para avaliação', $ai['criterios_instrumentos_avaliacao'] ?? '');
        $this->addWordSection($section, 'Mudanças para o próximo PEI', $ai['mudancas_proximo_pei'] ?? '');

        $section->addText('III – LEITURA DO PEI PELOS PROFISSIONAIS E EQUIPE MULTIDISCIPLINAR', ['bold' => true, 'size' => 12]);
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
