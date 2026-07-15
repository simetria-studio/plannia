<x-app-layout>
    <x-page-header breadcrumb="Configurações › Usuários › Editar" title="Editar Usuário" :back-url="route('users.index')" back-label="Voltar" />

    <div class="max-w-xl">
        <x-form-card title="Dados do Usuário">
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
                @csrf @method('PUT')
                <div>
                    <label class="plannia-label" for="name">Nome completo <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" class="plannia-input" value="{{ old('name', $user->name) }}" required>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="email">E-mail <span class="text-red-500">*</span></label>
                    <input id="email" name="email" type="email" class="plannia-input" value="{{ old('email', $user->email) }}" required>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="birth_date">Data de nascimento</label>
                    <input id="birth_date" name="birth_date" type="date" class="plannia-input" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                    <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="role">Perfil <span class="text-red-500">*</span></label>
                    <select id="role" name="role" class="plannia-select" required>
                        @foreach(\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="password">Nova senha (deixe em branco para manter)</label>
                    <input id="password" name="password" type="password" class="plannia-input">
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="password_confirmation">Confirmar nova senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="plannia-input">
                </div>
                <div class="flex justify-end"><button type="submit" class="plannia-btn-primary">Salvar</button></div>
            </form>
        </x-form-card>
    </div>
</x-app-layout>
