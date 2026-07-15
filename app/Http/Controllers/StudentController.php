<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Models\Student;
use App\Models\Turma;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = Student::with('turma')
            ->where('school_id', auth()->user()->school_id)
            ->latest()
            ->paginate(10);

        return view('students.index', compact('students'));
    }

    public function create(): View
    {
        $turmas = Turma::where('school_id', auth()->user()->school_id)
            ->orderBy('name')
            ->get();

        return view('students.create', compact('turmas'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $turma = Turma::findOrFail($request->turma_id);
        abort_unless($turma->school_id === $request->user()->school_id, 403);

        $student = Student::create([
            ...$request->validated(),
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('documents.create', $student)
            ->with('success', 'Aluno cadastrado! Agora faça o upload dos arquivos.');
    }

    public function show(Student $student): View
    {
        $this->authorizeSchool($student);
        $student->load(['turma', 'attachments', 'generatedDocuments']);

        return view('students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $this->authorizeSchool($student);

        $turmas = Turma::where('school_id', auth()->user()->school_id)
            ->orderBy('name')
            ->get();

        return view('students.edit', compact('student', 'turmas'));
    }

    public function update(StoreStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorizeSchool($student);

        $turma = Turma::findOrFail($request->turma_id);
        abort_unless($turma->school_id === $request->user()->school_id, 403);

        $student->update($request->validated());

        return redirect()->route('students.index')
            ->with('success', 'Aluno atualizado com sucesso.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorizeSchool($student);
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Aluno removido com sucesso.');
    }

    private function authorizeSchool(Student $student): void
    {
        abort_unless($student->school_id === auth()->user()->school_id, 403);
    }
}
