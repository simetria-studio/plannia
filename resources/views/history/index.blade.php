<x-app-layout>
    <x-page-header
        breadcrumb="Históricos"
        title="Histórico de Documentos"
        subtitle="Consulte todos os PEI e PAEE gerados, aprovados e pendentes."
    />

    <x-flash-messages />

    <div class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
        <div class="flex gap-1 border-b border-plannia-border">
            <a href="{{ route('history.index', ['tab' => 'all', 'search' => $search]) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $tab === 'all' ? 'border-plannia-blue text-plannia-blue' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Todos ({{ $counts['all'] }})
            </a>
            <a href="{{ route('history.index', ['tab' => 'approved', 'search' => $search]) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $tab === 'approved' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Aprovados ({{ $counts['approved'] }})
            </a>
            <a href="{{ route('history.index', ['tab' => 'pending', 'search' => $search]) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $tab === 'pending' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Pendentes ({{ $counts['pending'] }})
            </a>
        </div>

        <form method="GET" action="{{ route('history.index') }}" class="flex gap-2 w-full sm:w-auto">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input name="search" type="text" placeholder="Pesquisar por nome do aluno..." value="{{ $search }}" class="plannia-input w-full sm:w-72">
            <button type="submit" class="plannia-btn-primary">Buscar</button>
        </form>
    </div>

    <x-form-card title="Documentos">
        @if($documents->isEmpty())
            <p class="text-gray-500 text-sm">Nenhum documento encontrado.</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-plannia-border bg-gray-50/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aluno</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Formato</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Criado por</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Data</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-plannia-border">
                        @foreach($documents as $doc)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-3.5 text-sm font-medium text-gray-900">{{ $doc->student->full_name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $doc->type->label() }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-600 uppercase">{{ $doc->format }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $doc->isApproved() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $doc->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-gray-600">{{ $doc->creator->name }}</td>
                                <td class="px-6 py-3.5 text-sm text-gray-500">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3.5 text-right">
                                    <div class="flex justify-end gap-2 flex-wrap">
                                        <a href="{{ route('documents.download', $doc) }}" class="text-sm font-medium text-plannia-blue hover:text-plannia-blue-hover">Download</a>
                                        @if(auth()->user()->isDirecao())
                                            @if($doc->isPending())
                                                <form method="POST" action="{{ route('documents.approve', $doc) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-sm font-medium text-green-600 hover:text-green-700">Aprovar</button>
                                                </form>
                                            @endif
                                            @if($doc->isApproved())
                                                <form method="POST" action="{{ route('documents.share-email', $doc) }}" class="inline">@csrf<button type="submit" class="text-sm text-gray-500 hover:text-gray-700">E-mail</button></form>
                                                <form method="POST" action="{{ route('documents.share-whatsapp', $doc) }}" class="inline">@csrf<button type="submit" class="text-sm text-green-600 hover:text-green-700">WhatsApp</button></form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 px-2">{{ $documents->links() }}</div>
        @endif
    </x-form-card>
</x-app-layout>
