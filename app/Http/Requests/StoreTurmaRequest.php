<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isDirecao() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'turno' => ['required', 'string', 'in:Manhã,Tarde,Noite,Integral'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome da turma',
            'turno' => 'turno',
        ];
    }
}
