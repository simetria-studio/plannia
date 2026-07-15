<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTurmaRequest;
use App\Models\Turma;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TurmaController extends Controller
{
    public function index(): View
    {
        $turmas = Turma::with('school')
            ->where('school_id', auth()->user()->school_id)
            ->latest()
            ->paginate(10);

        return view('turmas.index', compact('turmas'));
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

        return redirect()->route('turmas.index')
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

        return redirect()->route('turmas.index')
            ->with('success', 'Turma atualizada com sucesso.');
    }

    public function destroy(Turma $turma): RedirectResponse
    {
        $this->authorizeSchool($turma);
        $turma->delete();

        return redirect()->route('turmas.index')
            ->with('success', 'Turma removida com sucesso.');
    }

    private function authorizeSchool(Turma $turma): void
    {
        abort_unless($turma->school_id === auth()->user()->school_id, 403);
    }
}
