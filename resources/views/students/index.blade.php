<x-app-layout>
    <x-page-header
        breadcrumb="Alunos e Turmas"
        title="Alunos e Turmas"
        subtitle="Gerencie os alunos cadastrados e gere documentos PEI/PAEE."
        :back-url="route('dashboard')"
        back-label="Voltar ao Início"
    />

    <x-flash-messages />

    <div class="flex justify-end mb-6">
        <a href="{{ route('students.create') }}" class="plannia-btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Aluno
        </a>
    </div>

    <x-form-card title="Lista de Alunos">
        @if($students->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="mt-3 text-gray-500">Nenhum aluno cadastrado.</p>
                <a href="{{ route('students.create') }}" class="mt-4 inline-block plannia-btn-primary">Cadastrar primeiro aluno</a>
            </div>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-plannia-border bg-gray-50/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Turma</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">CPF</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ingresso</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-plannia-border">
                        @foreach($students as $student)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-3.5 text-sm font-medium text-gray-900">{{ $student->full_name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $student->turma->name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $student->cpf }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $student->entry_year }}</td>
                                <td class="px-6 py-3.5 text-right space-x-3">
                                    <a href="{{ route('documents.create', $student) }}" class="text-sm font-medium text-plannia-blue hover:text-plannia-blue-hover">Gerar PEI/PAEE</a>
                                    <a href="{{ route('students.edit', $student) }}" class="text-sm text-gray-500 hover:text-gray-700">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 px-2">{{ $students->links() }}</div>
        @endif
    </x-form-card>
</x-app-layout>
