<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTurmaRequest;
use App\Models\Turma;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TurmaController extends Controller
{
    public function index()
    {
        return redirect()->route('students.index', ['tab' => 'turmas']);
    }

    public function create(): View
    {
        return view('turmas.create');
    }

    public function store(StoreTurmaRequest $request): RedirectResponse
    {
        Turma::create([
            ...$request->validated(),
            'school_id' => $request->user()->school_id,
        ]);

        return redirect()->route('students.index', ['tab' => 'turmas'])
            ->with('success', 'Turma cadastrada com sucesso.');
    }

    public function edit(Turma $turma): View
    {
        $this->authorizeSchool($turma);

        return view('turmas.edit', compact('turma'));
    }

    public function update(StoreTurmaRequest $request, Turma $turma): RedirectResponse
    {
        $this->authorizeSchool($turma);
        $turma->update($request->validated());

        return redirect()->route('students.index', ['tab' => 'turmas'])
            ->with('success', 'Turma atualizada com sucesso.');
    }

    public function destroy(Turma $turma): RedirectResponse
    {
        $this->authorizeSchool($turma);

        if ($turma->students()->exists()) {
            return redirect()->route('students.index', ['tab' => 'turmas'])
                ->with('error', 'Não é possível remover uma turma que possui alunos cadastrados.');
        }

        $turma->delete();

        return redirect()->route('students.index', ['tab' => 'turmas'])
            ->with('success', 'Turma removida com sucesso.');
    }

    private function authorizeSchool(Turma $turma): void
    {
        abort_unless($turma->school_id === auth()->user()->school_id, 403);
    }
}
