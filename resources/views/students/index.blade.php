<x-app-layout>
    <x-page-header
        breadcrumb="Alunos e Turmas"
        title="Alunos e Turmas"
        subtitle="Cadastre turmas e alunos da escola em um só lugar."
        :back-url="route('dashboard')"
        back-label="Voltar ao Início"
    />

    <x-flash-messages />

    <div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
        <div class="flex gap-1 border-b border-plannia-border">
            <a href="{{ route('students.index', ['tab' => 'alunos']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $tab === 'alunos' ? 'border-plannia-blue text-plannia-blue' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Alunos ({{ $counts['alunos'] }})
            </a>
            <a href="{{ route('students.index', ['tab' => 'turmas']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $tab === 'turmas' ? 'border-plannia-blue text-plannia-blue' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Turmas ({{ $counts['turmas'] }})
            </a>
        </div>

        <div class="flex gap-2">
            @if($tab === 'turmas')
                <a href="{{ route('turmas.create') }}" class="plannia-btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nova Turma
                </a>
            @else
                <a href="{{ route('turmas.create') }}" class="plannia-btn-secondary">
                    + Nova Turma
                </a>
                <a href="{{ route('students.create') }}" class="plannia-btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Novo Aluno
                </a>
            @endif
        </div>
    </div>

    @if($tab === 'turmas')
        <x-form-card title="Lista de Turmas">
            @if($turmas->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <p class="mt-3 text-gray-500">Nenhuma turma cadastrada.</p>
                    <p class="mt-1 text-sm text-gray-400">Cadastre as turmas antes de adicionar alunos.</p>
                    <a href="{{ route('turmas.create') }}" class="mt-4 inline-block plannia-btn-primary">Cadastrar primeira turma</a>
                </div>
            @else
                <div class="overflow-x-auto -mx-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-plannia-border bg-gray-50/50">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome da turma</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Turno</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alunos</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-plannia-border">
                            @foreach($turmas as $turma)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-3.5 text-sm font-medium text-gray-900">{{ $turma->name }}</td>
                                    <td class="px-6 py-3.5 text-sm text-gray-600">{{ $turma->turno }}</td>
                                    <td class="px-6 py-3.5 text-sm text-gray-600">{{ $turma->students_count }}</td>
                                    <td class="px-6 py-3.5 text-right space-x-3">
                                        <a href="{{ route('turmas.edit', $turma) }}" class="text-sm font-medium text-plannia-blue hover:text-plannia-blue-hover">Editar</a>
                                        <form method="POST" action="{{ route('turmas.destroy', $turma) }}" class="inline" onsubmit="return confirm('Remover esta turma?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm text-red-500 hover:text-red-700">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 px-2">{{ $turmas->appends(['tab' => 'turmas'])->links() }}</div>
            @endif
        </x-form-card>
    @else
        @if($counts['turmas'] === 0)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                <p class="font-semibold">Cadastre uma turma primeiro</p>
                <p class="mt-1">Para adicionar alunos, é necessário ter ao menos uma turma.</p>
                <a href="{{ route('turmas.create') }}" class="mt-3 inline-flex plannia-btn-primary">Cadastrar turma</a>
            </div>
        @endif

        <x-form-card title="Lista de Alunos">
            @if($students->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="mt-3 text-gray-500">Nenhum aluno cadastrado.</p>
                    @if($counts['turmas'] > 0)
                        <a href="{{ route('students.create') }}" class="mt-4 inline-block plannia-btn-primary">Cadastrar primeiro aluno</a>
                    @endif
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
                                    <td class="px-6 py-3.5 text-sm text-gray-600">{{ $student->turma->name }} — {{ $student->turma->turno }}</td>
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
                <div class="mt-4 px-2">{{ $students->appends(['tab' => 'alunos'])->links() }}</div>
            @endif
        </x-form-card>
    @endif
</x-app-layout>
