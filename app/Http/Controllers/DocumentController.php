<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Http\Requests\GenerateDocumentRequest;
use App\Models\GeneratedDocument;
use App\Models\Student;
use App\Models\StudentAttachment;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentGeneratorService $generator
    ) {}

    public function create(Student $student): View
    {
        $this->authorizeSchool($student);
        $student->load('turma');

        return view('documents.create', compact('student'));
    }

    public function store(GenerateDocumentRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeSchool($student);

        set_time_limit(300);

        $attachmentTypes = [
            'laudo_medico' => 'laudo_medico',
            'avaliacao_neuropsicologica' => 'avaliacao_neuropsicologica',
            'relatorio_escolar' => 'relatorio_escolar',
        ];

        $attachments = [];

        foreach ($attachmentTypes as $field => $type) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = $file->store("attachments/{$student->school_id}/{$student->id}", 'local');

            StudentAttachment::updateOrCreate(
                ['student_id' => $student->id, 'type' => $type],
                ['file_path' => $path, 'original_name' => $file->getClientOriginalName()]
            );

            $attachments[] = [
                'type' => $type,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        $format = $request->format === 'word' ? 'word' : 'pdf';
        $usedAi = (bool) config('services.openai.api_key');

        $this->generator->generateCombined(
            $student,
            $format,
            $request->user(),
            $attachments
        );

        $message = 'PEI e PAEE gerados no mesmo arquivo com sucesso!';
        $message .= $usedAi
            ? ' A IA extraiu dados dos uploads e montou o conteúdo.'
            : ' Conteúdo montado em modo parcial (configure OPENAI_API_KEY para IA completa).';

        if ($request->user()->isProfessor()) {
            $message .= ' Aguardando aprovação da direção.';
        }

        return redirect()->route('history.index')
            ->with('success', $message);
    }

    public function download(GeneratedDocument $document): StreamedResponse
    {
        $this->authorizeDocument($document);

        return Storage::disk('local')->download(
            $document->file_path,
            $this->downloadFilename($document)
        );
    }

    public function approve(GeneratedDocument $document): RedirectResponse
    {
        abort_unless(auth()->user()->isDirecao(), 403);
        $this->authorizeDocument($document);

        $document->update([
            'status' => DocumentStatus::Aprovado,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Documento aprovado com sucesso.');
    }

    public function shareEmail(GeneratedDocument $document): RedirectResponse
    {
        abort_unless(auth()->user()->isDirecao(), 403);
        $this->authorizeDocument($document);
        abort_unless($document->isApproved(), 403, 'Apenas documentos aprovados podem ser compartilhados.');

        $student = $document->student;

        if (! $student->email) {
            return back()->with('error', 'O aluno não possui e-mail cadastrado.');
        }

        $filePath = Storage::disk('local')->path($document->file_path);

        Mail::raw(
            "Segue em anexo o documento {$document->type->label()} do aluno {$student->full_name}.",
            function ($message) use ($student, $document, $filePath) {
                $message->to($student->email)
                    ->subject("{$document->type->label()} - {$student->full_name}")
                    ->attach($filePath, ['as' => $this->downloadFilename($document)]);
            }
        );

        $document->update(['shared_via_email_at' => now()]);

        return back()->with('success', 'Documento enviado por e-mail com sucesso.');
    }

    public function shareWhatsapp(GeneratedDocument $document): RedirectResponse
    {
        abort_unless(auth()->user()->isDirecao(), 403);
        $this->authorizeDocument($document);
        abort_unless($document->isApproved(), 403, 'Apenas documentos aprovados podem ser compartilhados.');

        $student = $document->student;

        if (! $student->whatsapp) {
            return back()->with('error', 'O aluno não possui WhatsApp cadastrado.');
        }

        $phone = preg_replace('/\D/', '', $student->whatsapp);
        $message = urlencode(
            "Olá! Segue o documento {$document->type->label()} do aluno {$student->full_name}. ".
            'Acesse o sistema PLANNIA para fazer o download.'
        );

        $document->update(['shared_via_whatsapp_at' => now()]);

        return redirect()->away("https://wa.me/{$phone}?text={$message}");
    }

    private function authorizeSchool(Student $student): void
    {
        abort_unless($student->school_id === auth()->user()->school_id, 403);
    }

    private function authorizeDocument(GeneratedDocument $document): void
    {
        abort_unless($document->school_id === auth()->user()->school_id, 403);
    }

    private function downloadFilename(GeneratedDocument $document): string
    {
        $ext = $document->format === 'word' ? 'docx' : 'pdf';

        return "{$document->type->value}_{$document->student->full_name}.{$ext}";
    }
}
