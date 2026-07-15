<x-app-layout>
    <x-page-header
        breadcrumb="Alunos e Turmas <span class='text-gray-400 mx-1'>›</span> Editar"
        title="Editar Aluno"
        :back-url="route('students.index')"
        back-label="Voltar para Alunos"
    />

    <div class="max-w-4xl">
        <x-form-card title="Dados do Aluno">
            <form method="POST" action="{{ route('students.update', $student) }}" class="space-y-5">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="plannia-label" for="full_name">Nome completo <span class="text-red-500">*</span></label>
                        <input id="full_name" name="full_name" type="text" class="plannia-input" value="{{ old('full_name', $student->full_name) }}" required>
                        <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="turma_id">Turma <span class="text-red-500">*</span></label>
                        <select id="turma_id" name="turma_id" class="plannia-select" required>
                            @foreach($turmas as $turma)
                                <option value="{{ $turma->id }}" @selected(old('turma_id', $student->turma_id) == $turma->id)>{{ $turma->name }} — {{ $turma->turno }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('turma_id')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="birth_date">Data de nascimento</label>
                        <input id="birth_date" name="birth_date" type="date" class="plannia-input" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}">
                        <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="cpf">CPF <span class="text-red-500">*</span></label>
                        <input id="cpf" name="cpf" type="text" class="plannia-input" value="{{ old('cpf', $student->cpf) }}" required>
                        <x-input-error :messages="$errors->get('cpf')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="legal_guardian">Responsável legal <span class="text-red-500">*</span></label>
                        <input id="legal_guardian" name="legal_guardian" type="text" class="plannia-input" value="{{ old('legal_guardian', $student->legal_guardian) }}" required>
                        <x-input-error :messages="$errors->get('legal_guardian')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="whatsapp">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" type="text" class="plannia-input" value="{{ old('whatsapp', $student->whatsapp) }}">
                        <x-input-error :messages="$errors->get('whatsapp')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="email">E-mail</label>
                        <input id="email" name="email" type="email" class="plannia-input" value="{{ old('email', $student->email) }}">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="plannia-label" for="address">Endereço <span class="text-red-500">*</span></label>
                        <input id="address" name="address" type="text" class="plannia-input" value="{{ old('address', $student->address) }}" required>
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="entry_year">Ano de ingresso <span class="text-red-500">*</span></label>
                        <input id="entry_year" name="entry_year" type="number" class="plannia-input" value="{{ old('entry_year', $student->entry_year) }}" required>
                        <x-input-error :messages="$errors->get('entry_year')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label">Laudo médico <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-4 mt-2">
                            @foreach(\App\Enums\MedicalReportStatus::cases() as $status)
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="medical_report_status" value="{{ $status->value }}" @checked(old('medical_report_status', $student->medical_report_status->value) === $status->value) class="text-plannia-blue" required>
                                    <span class="text-sm">{{ $status->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('medical_report_status')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="cid">CID <span class="text-red-500">*</span></label>
                        <input id="cid" name="cid" type="text" class="plannia-input" value="{{ old('cid', $student->cid) }}" required>
                        <x-input-error :messages="$errors->get('cid')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="plannia-label" for="observations">Observações <span class="text-red-500">*</span></label>
                        <textarea id="observations" name="observations" rows="4" class="plannia-input resize-none" required>{{ old('observations', $student->observations) }}</textarea>
                        <x-input-error :messages="$errors->get('observations')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-plannia-border">
                    <a href="{{ route('students.index') }}" class="plannia-btn-ghost">Cancelar</a>
                    <button type="submit" class="plannia-btn-primary">Salvar alterações</button>
                </div>
            </form>
        </x-form-card>
    </div>
</x-app-layout>
