<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'laudo_medico' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'avaliacao_neuropsicologica' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'relatorio_escolar' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'document_types' => ['required', 'array', 'min:1'],
            'document_types.*' => ['required', Rule::in(['pei', 'paee'])],
            'combine_mode' => ['nullable', Rule::in(['separate', 'combined'])],
            'format' => ['required', Rule::in(['pdf', 'word'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'laudo_medico' => 'laudo médico',
            'avaliacao_neuropsicologica' => 'avaliação neuropsicológica',
            'relatorio_escolar' => 'relatório escolar',
            'document_types' => 'tipo de documento',
            'combine_mode' => 'modo de geração',
            'format' => 'formato',
        ];
    }
}
