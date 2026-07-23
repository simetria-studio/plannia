<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDirecao() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'phone' => ['nullable', 'string', 'max:30'],
            'inep' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome da escola',
            'address' => 'endereço',
            'cnpj' => 'CNPJ',
            'phone' => 'telefone',
            'inep' => 'código INEP',
            'logo' => 'logo',
        ];
    }
}
