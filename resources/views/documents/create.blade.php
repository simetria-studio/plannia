<x-app-layout>
    <x-page-header
        breadcrumb="PEI e PAEE <span class='text-gray-400 mx-1'>›</span> Upload"
        :title="'Gerar PEI / PAEE — ' . $student->full_name"
        subtitle="Envie os documentos clínicos/escolares. A IA extrai os dados e monta o PEI/PAEE automaticamente."
        :back-url="route('students.index')"
        back-label="Voltar para Alunos"
    />

    <x-flash-messages />

    <div class="mb-6 rounded-xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">Geração com IA</p>
        <p>O sistema lê cada arquivo (OCR em fotos/scans), extrai diagnóstico, CID, especialistas e orientações, e monta o PEI/PAEE. Fotos de laudo e relatório funcionam — a geração pode levar cerca de 30 a 90 segundos.</p>
        @unless(config('services.openai.api_key'))
            <p class="mt-2 text-amber-700 font-medium">OPENAI_API_KEY não configurada — será usado um modo parcial até você adicionar a chave no .env.</p>
        @endunless
    </div>

    <div class="mb-6 plannia-card px-6 py-4 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-plannia-blue/10 text-plannia-blue font-semibold text-sm">
            {{ strtoupper(substr($student->full_name, 0, 1)) }}
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900">{{ $student->full_name }}</p>
            <p class="text-xs text-gray-500">{{ $student->turma->name }} — {{ $student->turma->turno }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('documents.store', $student) }}" enctype="multipart/form-data"
          x-data="{
              loading: false,
              pei: {{ in_array('pei', old('document_types', [])) ? 'true' : 'false' }},
              paee: {{ in_array('paee', old('document_types', [])) ? 'true' : 'false' }},
              get both() { return this.pei && this.paee }
          }"
          @submit="loading = true">
        @csrf

        <div class="space-y-6">
            <x-form-card title="1. Upload dos arquivos" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12\'/></svg>'">
                <div class="space-y-5">
                    @foreach([
                        'laudo_medico' => ['label' => 'Laudo médico', 'required' => false],
                        'avaliacao_neuropsicologica' => ['label' => 'Avaliação neuropsicológica', 'required' => true],
                        'relatorio_escolar' => ['label' => 'Relatório escolar', 'required' => true],
                    ] as $field => $meta)
                        <div>
                            <label class="plannia-label" for="{{ $field }}">
                                {{ $meta['label'] }}
                                @if($meta['required'])
                                    <span class="text-red-500">*</span>
                                @else
                                    <span class="text-gray-400 font-normal">(opcional)</span>
                                @endif
                            </label>
                            <input
                                id="{{ $field }}"
                                name="{{ $field }}"
                                type="file"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-plannia-blue hover:file:bg-blue-100 transition"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                @if($meta['required']) required @endif
                            >
                            <x-input-error :messages="$errors->get($field)" class="mt-1" />
                        </div>
                    @endforeach
                </div>
            </x-form-card>

            <x-form-card title="2. Selecionar documento(s)" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4\'/></svg>'">
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="document_types[]" value="pei" x-model="pei" class="rounded text-plannia-blue focus:ring-plannia-blue">
                        <span class="text-sm font-medium text-gray-700">PEI</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="document_types[]" value="paee" x-model="paee" class="rounded text-plannia-blue focus:ring-plannia-blue">
                        <span class="text-sm font-medium text-gray-700">PAEE</span>
                    </label>

                    <div class="w-full mt-2 pt-3 border-t border-gray-100" x-show="both" x-cloak>
                        <p class="text-sm font-medium text-gray-700 mb-2">Como gerar PEI e PAEE?</p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="combine_mode" value="separate" @checked(old('combine_mode', 'separate') === 'separate') class="text-plannia-blue focus:ring-plannia-blue">
                                <span class="text-sm text-gray-700">Arquivos separados</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="combine_mode" value="combined" @checked(old('combine_mode') === 'combined') class="text-plannia-blue focus:ring-plannia-blue">
                                <span class="text-sm text-gray-700">Mesmo arquivo (PEI + PAEE)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('document_types')" class="mt-2" />
                <x-input-error :messages="$errors->get('combine_mode')" class="mt-2" />
            </x-form-card>

            <x-form-card title="3. Formato final" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z\'/></svg>'">
                <div class="flex gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="format" value="pdf" @checked(old('format', 'pdf') === 'pdf') class="text-plannia-blue focus:ring-plannia-blue" required>
                        <span class="text-sm font-medium text-gray-700">PDF</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="format" value="word" @checked(old('format') === 'word') class="text-plannia-blue focus:ring-plannia-blue">
                        <span class="text-sm font-medium text-gray-700">Word</span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('format')" class="mt-2" />
            </x-form-card>

            @if(auth()->user()->isProfessor())
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    Como professor, os documentos gerados ficarão aguardando aprovação da direção antes de poderem ser compartilhados.
                </div>
            @endif
        </div>

        <div class="mt-8 flex justify-end gap-3 border-t border-plannia-border pt-6">
            <a href="{{ route('students.index') }}" class="plannia-btn-ghost" x-show="!loading">Cancelar</a>
            <button type="submit" class="plannia-btn-primary" :disabled="loading">
                <template x-if="!loading">
                    <span class="inline-flex items-center gap-2">
                        Gerar com IA
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                </template>
                <template x-if="loading">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Extraindo dados e montando o documento...
                    </span>
                </template>
            </button>
        </div>
    </form>
</x-app-layout>
