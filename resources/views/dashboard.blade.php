<x-app-layout>
    <x-page-header
        title="Início"
        subtitle="Visão geral do sistema PLANNIA"
    />

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="plannia-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Alunos cadastrados</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['students'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-plannia-blue">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="plannia-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Documentos gerados</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['documents'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
        </div>
        <div class="plannia-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Aprovados</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="plannia-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Aguardando aprovação</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1">{{ $stats['pending'] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="flex gap-3 mb-8">
        <a href="{{ route('students.create') }}" class="plannia-btn-primary">+ Novo Aluno</a>
        <a href="{{ route('history.index') }}" class="plannia-btn-secondary">Ver Histórico</a>
    </div>

    <x-form-card title="Documentos recentes">
        @if($recentDocuments->isEmpty())
            <p class="text-gray-500 text-sm">Nenhum documento gerado ainda.</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-plannia-border bg-gray-50/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-plannia-border">
                        @foreach($recentDocuments as $doc)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-3.5 text-sm font-medium text-gray-900">{{ $doc->student->full_name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $doc->type->label() }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->isApproved() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $doc->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-gray-500">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-form-card>
</x-app-layout>
