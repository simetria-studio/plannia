<x-app-layout>
    <x-page-header breadcrumb="Configurações <span class='text-gray-400 mx-1'>›</span> Turmas" title="Turmas" subtitle="Gerencie as turmas da escola." :back-url="route('schools.edit')" back-label="Voltar" />

    <x-flash-messages />

    <div class="flex justify-end mb-6">
        <a href="{{ route('turmas.create') }}" class="plannia-btn-primary">+ Nova Turma</a>
    </div>

    <x-form-card title="Lista de Turmas">
        @if($turmas->isEmpty())
            <p class="text-gray-500 text-sm">Nenhuma turma cadastrada.</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full">
                    <thead><tr class="border-b border-plannia-border bg-gray-50/50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Turno</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                    </tr></thead>
                    <tbody class="divide-y divide-plannia-border">
                        @foreach($turmas as $turma)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-6 py-3.5 text-sm font-medium">{{ $turma->name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $turma->turno }}</td>
                                <td class="px-6 py-3.5 text-right space-x-3">
                                    <a href="{{ route('turmas.edit', $turma) }}" class="text-sm text-plannia-blue">Editar</a>
                                    <form method="POST" action="{{ route('turmas.destroy', $turma) }}" class="inline" onsubmit="return confirm('Remover?')">@csrf @method('DELETE')<button class="text-sm text-red-500">Remover</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $turmas->links() }}</div>
        @endif
    </x-form-card>
</x-app-layout>
