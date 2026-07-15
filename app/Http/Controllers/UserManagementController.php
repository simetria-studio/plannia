<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::where('school_id', auth()->user()->school_id)
            ->whereIn('role', [UserRole::Direcao, UserRole::Professor])
            ->latest()
            ->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'school_id' => $request->user()->school_id,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $user): View
    {
        $this->authorizeSchool($user);

        return view('users.edit', compact('user'));
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeSchool($user);

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeSchool($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode remover seu próprio usuário.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuário removido com sucesso.');
    }

    private function authorizeSchool(User $user): void
    {
        abort_unless($user->school_id === auth()->user()->school_id, 403);
    }
}
