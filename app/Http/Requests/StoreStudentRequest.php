<?php

namespace App\Http\Requests;

use App\Enums\MedicalReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'turma_id' => ['required', 'exists:turmas,id'],
            'birth_date' => ['nullable', 'date'],
            'cpf' => ['required', 'string', 'max:14'],
            'legal_guardian' => ['required', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'entry_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'medical_report_status' => ['required', Rule::enum(MedicalReportStatus::class)],
            'cid' => ['required', 'string', 'max:50'],
            'observations' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nome completo',
            'turma_id' => 'turma',
            'birth_date' => 'data de nascimento',
            'cpf' => 'CPF',
            'legal_guardian' => 'responsável legal',
            'whatsapp' => 'WhatsApp',
            'email' => 'e-mail',
            'address' => 'endereço',
            'entry_year' => 'ano de ingresso',
            'medical_report_status' => 'laudo médico',
            'cid' => 'CID',
            'observations' => 'observações',
        ];
    }
}
