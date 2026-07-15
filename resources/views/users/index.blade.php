<x-app-layout>
    <x-page-header breadcrumb="Configurações › Usuários" title="Direção e Professores" subtitle="Gerencie os usuários com acesso ao sistema." :back-url="route('schools.edit')" back-label="Voltar" />

    <x-flash-messages />

    <div class="flex justify-end mb-6">
        <a href="{{ route('users.create') }}" class="plannia-btn-primary">+ Novo Usuário</a>
    </div>

    <x-form-card title="Lista de Usuários">
        @if($users->isEmpty())
            <p class="text-gray-500 text-sm">Nenhum usuário cadastrado.</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full">
                    <thead><tr class="border-b border-plannia-border bg-gray-50/50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">E-mail</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Perfil</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                    </tr></thead>
                    <tbody class="divide-y divide-plannia-border">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-6 py-3.5 text-sm font-medium">{{ $user->name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $user->email }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->isDirecao() ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ $user->role->label() }}</span>
                                </td>
                                <td class="px-6 py-3.5 text-right space-x-3">
                                    <a href="{{ route('users.edit', $user) }}" class="text-sm text-plannia-blue">Editar</a>
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Remover?')">@csrf @method('DELETE')<button class="text-sm text-red-500">Remover</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        @endif
    </x-form-card>
</x-app-layout>
