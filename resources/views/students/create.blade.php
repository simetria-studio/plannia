<x-app-layout>
    <x-page-header
        breadcrumb="Alunos e Turmas <span class='text-gray-400 mx-1'>›</span> Cadastro de aluno"
        title="Cadastro de Aluno"
        subtitle="Preencha as informações do aluno e selecione a turma."
        :back-url="route('students.index')"
        back-label="Voltar para Alunos e Turmas"
    />

    <x-flash-messages />

    @if($turmas->isEmpty())
        <div class="plannia-card p-6 border-amber-200 bg-amber-50 text-amber-800">
            <p class="font-semibold">Nenhuma turma cadastrada</p>
            <p class="mt-1 text-sm">Cadastre uma turma antes de registrar alunos.</p>
            <a href="{{ route('turmas.create') }}" class="mt-4 inline-flex plannia-btn-primary">Cadastrar turma</a>
        </div>
    @else
        <form method="POST" action="{{ route('students.store') }}" id="student-form" class="space-y-6">
            @csrf

            <x-form-card title="Dados do Aluno" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z\'/></svg>'">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="plannia-label" for="full_name">Nome completo <span class="text-red-500">*</span></label>
                        <input id="full_name" name="full_name" type="text" class="plannia-input" placeholder="Digite o nome completo do aluno" value="{{ old('full_name') }}" required>
                        <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="turma_id">Turma <span class="text-red-500">*</span></label>
                        <select id="turma_id" name="turma_id" class="plannia-select" required>
                            <option value="">Selecione a turma...</option>
                            @foreach($turmas as $turma)
                                <option value="{{ $turma->id }}" @selected(old('turma_id') == $turma->id)>
                                    {{ $turma->name }} — {{ $turma->turno }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Não encontrou a turma?
                            <a href="{{ route('turmas.create') }}" class="text-plannia-blue hover:underline">Cadastrar nova turma</a>
                        </p>
                        <x-input-error :messages="$errors->get('turma_id')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="birth_date">Data de nascimento <span class="text-red-500">*</span></label>
                        <input id="birth_date" name="birth_date" type="date" class="plannia-input" value="{{ old('birth_date') }}">
                        <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="cpf">CPF <span class="text-red-500">*</span></label>
                        <input id="cpf" name="cpf" type="text" class="plannia-input" placeholder="000.000.000-00" value="{{ old('cpf') }}" required>
                        <x-input-error :messages="$errors->get('cpf')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="legal_guardian">Responsável legal <span class="text-red-500">*</span></label>
                        <input id="legal_guardian" name="legal_guardian" type="text" class="plannia-input" placeholder="Nome do responsável" value="{{ old('legal_guardian') }}" required>
                        <x-input-error :messages="$errors->get('legal_guardian')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="whatsapp">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" type="text" class="plannia-input" placeholder="(00) 00000-0000" value="{{ old('whatsapp') }}">
                        <x-input-error :messages="$errors->get('whatsapp')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="email">E-mail do responsável</label>
                        <input id="email" name="email" type="email" class="plannia-input" placeholder="email@exemplo.com" value="{{ old('email') }}">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="plannia-label" for="address">Endereço <span class="text-red-500">*</span></label>
                        <input id="address" name="address" type="text" class="plannia-input" placeholder="Rua, número, bairro, cidade" value="{{ old('address') }}" required>
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>

                    <div>
                        <label class="plannia-label" for="entry_year">Ano de ingresso <span class="text-red-500">*</span></label>
                        <select id="entry_year" name="entry_year" class="plannia-select" required>
                            @for($y = date('Y'); $y >= 2000; $y--)
                                <option value="{{ $y }}" @selected(old('entry_year', date('Y')) == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('entry_year')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-5 flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-700">
                    <svg class="h-4 w-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    As informações marcadas com <span class="text-red-500 font-medium">*</span> são obrigatórias.
                </div>
            </x-form-card>

            <x-form-card title="Informações Acadêmicas" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M12 14l9-5-9-5-9 5 9 5z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z\'/></svg>'">
                <div class="space-y-5">
                    <div>
                        <label class="plannia-label">Laudo médico <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-4 mt-2">
                            @foreach(\App\Enums\MedicalReportStatus::cases() as $status)
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="medical_report_status" value="{{ $status->value }}" @checked(old('medical_report_status') === $status->value) class="text-plannia-blue focus:ring-plannia-blue" required>
                                    <span class="text-sm text-gray-700">{{ $status->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('medical_report_status')" class="mt-1" />
                    </div>

                    <div class="max-w-md">
                        <label class="plannia-label" for="cid">CID (Código Internacional de Doenças) <span class="text-red-500">*</span></label>
                        <input id="cid" name="cid" type="text" class="plannia-input" placeholder="Ex: F84.0" value="{{ old('cid') }}" required>
                        <x-input-error :messages="$errors->get('cid')" class="mt-1" />
                    </div>
                </div>
            </x-form-card>

            <x-form-card title="Observações" :icon="'<svg class=\'h-5 w-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.75\' d=\'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z\'/></svg>'">
                <div>
                    <textarea id="observations" name="observations" rows="5" maxlength="1000" class="plannia-input resize-none" placeholder="Informações adicionais relevantes sobre o aluno..." required oninput="document.getElementById('obs-count').textContent = this.value.length">{{ old('observations') }}</textarea>
                    <div class="flex justify-between mt-1">
                        <x-input-error :messages="$errors->get('observations')" />
                        <span class="text-xs text-gray-400"><span id="obs-count">0</span>/1000 caracteres</span>
                    </div>
                </div>
            </x-form-card>

            <div class="flex items-center justify-end gap-3 border-t border-plannia-border pt-6">
                <a href="{{ route('students.index') }}" class="plannia-btn-ghost">Cancelar</a>
                <button type="reset" class="plannia-btn-secondary">Limpar campos</button>
                <button type="submit" class="plannia-btn-primary">
                    Salvar e continuar
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </form>
    @endif
</x-app-layout>
