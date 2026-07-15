<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDirecao() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'birth_date' => ['nullable', 'date'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [$userId ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome completo',
            'email' => 'e-mail',
            'birth_date' => 'data de nascimento',
            'role' => 'perfil',
            'password' => 'senha',
        ];
    }
}
