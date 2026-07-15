<x-app-layout>
    <x-page-header breadcrumb="Configurações › Turmas › Editar" title="Editar Turma" :back-url="route('turmas.index')" back-label="Voltar" />

    <div class="max-w-xl">
        <x-form-card title="Dados da Turma">
            <form method="POST" action="{{ route('turmas.update', $turma) }}" class="space-y-5">
                @csrf @method('PUT')
                <div>
                    <label class="plannia-label" for="name">Nome da turma <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" class="plannia-input" value="{{ old('name', $turma->name) }}" required>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="turno">Turno <span class="text-red-500">*</span></label>
                    <select id="turno" name="turno" class="plannia-select" required>
                        @foreach(['Manhã', 'Tarde', 'Noite', 'Integral'] as $turno)
                            <option value="{{ $turno }}" @selected(old('turno', $turma->turno) === $turno)>{{ $turno }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('turno')" class="mt-1" />
                </div>
                <div class="flex justify-end"><button type="submit" class="plannia-btn-primary">Salvar</button></div>
            </form>
        </x-form-card>
    </div>
</x-app-layout>
