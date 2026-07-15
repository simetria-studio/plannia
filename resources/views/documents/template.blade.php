<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4F46E5; padding-bottom: 15px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        .school-name { font-size: 18px; font-weight: bold; color: #4F46E5; }
        .doc-title { font-size: 16px; font-weight: bold; margin: 20px 0; text-align: center; background: #EEF2FF; padding: 10px; }
        .field { margin-bottom: 8px; }
        .label { font-weight: bold; }
        .observations { margin-top: 20px; padding: 15px; background: #F9FAFB; border-left: 4px solid #4F46E5; }
        .footer { margin-top: 40px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        @if($school->logo_path && file_exists(storage_path('app/public/' . $school->logo_path)))
            <img src="{{ storage_path('app/public/' . $school->logo_path) }}" class="logo">
        @endif
        <div class="school-name">{{ $school->name }}</div>
        @if($school->address)
            <div>{{ $school->address }}</div>
        @endif
        @if($school->cnpj)
            <div>CNPJ: {{ $school->cnpj }}</div>
        @endif
    </div>

    <div class="doc-title">{{ $type->label() }} — Plano Educacional Individualizado</div>

    <div class="field"><span class="label">Aluno:</span> {{ $student->full_name }}</div>
    <div class="field"><span class="label">Turma:</span> {{ $student->turma->name }} — {{ $student->turma->turno }}</div>
    @if($student->birth_date)
        <div class="field"><span class="label">Data de nascimento:</span> {{ $student->birth_date->format('d/m/Y') }}</div>
    @endif
    <div class="field"><span class="label">CPF:</span> {{ $student->cpf }}</div>
    <div class="field"><span class="label">Responsável legal:</span> {{ $student->legal_guardian }}</div>
    @if($student->whatsapp)
        <div class="field"><span class="label">WhatsApp:</span> {{ $student->whatsapp }}</div>
    @endif
    @if($student->email)
        <div class="field"><span class="label">E-mail:</span> {{ $student->email }}</div>
    @endif
    <div class="field"><span class="label">Endereço:</span> {{ $student->address }}</div>
    <div class="field"><span class="label">Ano de ingresso:</span> {{ $student->entry_year }}</div>
    <div class="field"><span class="label">Laudo médico:</span> {{ $student->medical_report_status->label() }}</div>
    <div class="field"><span class="label">CID:</span> {{ $student->cid }}</div>

    <div class="observations">
        <div class="label">Observações:</div>
        <div>{{ $student->observations }}</div>
    </div>

    <div class="footer">
        Documento gerado em {{ $generated_at }} — PLANNIA
    </div>
</body>
</html>
