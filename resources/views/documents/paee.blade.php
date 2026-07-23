<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #111; line-height: 1.35; }
        .school { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .logo-wrap { text-align: center; margin: 0 0 4px; }
        .logo { width: 80px; height: 80px; }
        .school-meta { text-align: center; font-size: 8.5px; color: #444; margin: 2px 0 6px; line-height: 1.4; }
        .school-meta span { display: inline-block; margin: 0 5px; }
        .doc-title { text-align: center; font-size: 12px; font-weight: bold; margin: 8px 0 10px; text-transform: uppercase; }
        .section-title { font-size: 10px; font-weight: bold; margin: 10px 0 6px; text-transform: uppercase; border-bottom: 1px solid #333; padding-bottom: 2px; }
        .label { font-weight: bold; }
        .check { display: inline-block; margin-right: 6px; white-space: nowrap; }
        .row { margin-bottom: 3px; }
        .muted { color: #444; font-size: 8.5px; margin: 3px 0 6px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.info td { vertical-align: top; padding: 1px 3px 1px 0; }
        table.grid { width: 100%; border-collapse: collapse; margin: 6px 0 10px; }
        table.grid th, table.grid td { border: 1px solid #555; padding: 3px 4px; vertical-align: middle; }
        table.grid th { background: #f3f4f6; font-size: 8px; text-align: center; }
        table.grid td.skill { width: 48%; font-size: 8.5px; }
        table.grid td.mark { text-align: center; width: 13%; font-size: 9px; }
        .group { font-weight: bold; font-size: 9.5px; margin: 8px 0 3px; background: #eef2ff; padding: 3px 5px; }
        .box { border: 1px solid #ccc; padding: 6px; margin: 4px 0 8px; min-height: 28px; }
        .sign { margin-top: 10px; }
        .sign-item { margin-bottom: 10px; }
        .footer { margin-top: 10px; font-size: 8px; color: #666; text-align: center; }
    </style>
</head>
<body>
@php
    use App\Support\PaeeSkills;

    $especialistasLista = [
        'Neurologista', 'Psiquiatra', 'Psicólogo', 'Fonoaudiólogo', 'T.O.',
        'Psicomotricista', 'Nutricionista', 'ABA', 'Psicopedagogia ou Neuropsicopedagogia', 'Musicoterapia',
    ];
    $marcados = collect($ai['especialistas'] ?? [])->map(fn ($e) => mb_strtolower(trim($e)))->all();
    $sexo = strtoupper((string) ($ai['sexo'] ?? ''));
    $resumo = $ai['resumo_areas'] ?? [];
    $habilidades = $ai['habilidades'] ?? [];
    $areaLevels = PaeeSkills::areaLevels();
    $skillLevels = PaeeSkills::skillLevels();

    $markArea = function (string $key, string $level) use ($resumo) {
        return (($resumo[$key] ?? '') === $level) ? 'X' : '';
    };
    $markSkill = function (int|string $number, string $level) use ($habilidades) {
        $value = $habilidades[(string) $number] ?? $habilidades[$number] ?? 'nao_observado';

        return $value === $level ? 'X' : '';
    };
@endphp

@include('documents.partials.school-header', [
    'headerTitle' => $ai['titulo'] ?? 'PAEE (PLANO DE ATENDIMENTO EDUCACIONAL ESPECIALIZADO)',
])

<div class="section-title">I – Informações do aluno</div>
<table class="info">
    <tr>
        <td style="width:62%"><span class="label">Nome completo do aluno:</span> {{ $student->full_name }}</td>
        <td><span class="label">Data de nascimento:</span> {{ $student->birth_date?->format('d/m/Y') ?? '____/____/______' }}</td>
    </tr>
    <tr>
        <td>
            <span class="label">Sexo:</span>
            ({{ $sexo === 'F' ? 'x' : ' ' }}) F
            ({{ $sexo === 'M' ? 'x' : ' ' }}) M
            &nbsp;&nbsp;<span class="label">Série:</span> {{ $ai['serie'] ?? $student->turma->name }}
            &nbsp;&nbsp;<span class="label">Turno:</span> {{ $ai['turno'] ?? $student->turma->turno }}
        </td>
        <td><span class="label">Diagnóstico:</span> {{ $ai['diagnostico'] ?? $student->cid }}</td>
    </tr>
</table>

<div class="row"><span class="label">Acompanhamento com especialistas:</span></div>
<div class="row">
    @foreach($especialistasLista as $esp)
        @php
            $marcado = collect($marcados)->contains(fn ($m) => str_contains($m, mb_strtolower($esp)) || str_contains(mb_strtolower($esp), $m));
        @endphp
        <span class="check">({{ $marcado ? 'x' : '  ' }}) {{ $esp }}</span>
    @endforeach
</div>

<div class="row" style="margin-top:6px;">
    <span class="label">AVALIAÇÃO DE OBSERVAÇÃO DO PERÍODO:</span>
    {{ $ai['avaliacao_periodo_inicio'] ?? '____/____/______' }}
    à
    {{ $ai['avaliacao_periodo_fim'] ?? '____/____/______' }}
</div>
<div class="row"><span class="label">Observações:</span></div>
<div class="box">{{ $ai['observacoes_periodo'] ?? '' }}</div>

<div class="section-title">Resumo por áreas</div>
<table class="grid">
    <thead>
        <tr>
            <th style="width:32%;">Habilidades</th>
            @foreach($areaLevels as $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach(PaeeSkills::areas() as $key => $label)
            <tr>
                <td class="skill">{{ $label }}</td>
                @foreach(array_keys($areaLevels) as $level)
                    <td class="mark">{{ $markArea($key, $level) }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

@foreach(PaeeSkills::groups() as $group => $skills)
    <div class="group">{{ $group }}</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width:48%;">Habilidades</th>
                @foreach($skillLevels as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($skills as $number => $label)
                <tr>
                    <td class="skill">{{ $number }} – {{ $label }}</td>
                    @foreach(array_keys($skillLevels) as $level)
                        <td class="mark">{{ $markSkill($number, $level) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

<p class="muted">Observação: caso a habilidade não esteja de acordo com a faixa etária do aluno, será marcada a opção: (Não foi observado).</p>

<div class="row"><span class="label">Material didático utilizado pelo aluno:</span></div>
<div class="box">{{ $ai['material_didatico'] ?? '' }}</div>

<div class="row"><span class="label">Solicitações à família:</span></div>
<div class="box">{{ $ai['solicitacoes_familia'] ?? '' }}</div>

<div class="row"><span class="label">Encaminhamento:</span></div>
<div class="box">{{ $ai['encaminhamento'] ?? '' }}</div>

<div class="row"><span class="label">Observações gerais:</span></div>
<div class="box">{{ $ai['observacoes_gerais'] ?? '' }}</div>

<div class="sign">
    <div class="sign-item">Assinatura do professor: _____________________________________________________________</div>
    <div class="sign-item">Assinatura da coordenação: ___________________________________________________________</div>
    <div class="sign-item">Assinatura da direção: _______________________________________________________________</div>
    <div class="sign-item">Assinatura do responsável: ___________________________________________________________</div>
</div>

<div class="footer">
    Documento gerado em {{ $generated_at }} — PLANNIA
    @if(!empty($ai['_fallback']))
        <br>Observação: conteúdo gerado em modo parcial.
    @endif
</div>
</body>
</html>
