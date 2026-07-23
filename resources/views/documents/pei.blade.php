<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; line-height: 1.4; }
        .school { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .logo-wrap { text-align: center; margin: 0 0 6px; }
        .logo { width: 90px; height: 90px; }
        .school-meta { text-align: center; font-size: 9px; color: #444; margin: 2px 0 8px; line-height: 1.45; }
        .school-meta span { display: inline-block; margin: 0 6px; }
        .doc-title { text-align: center; font-size: 12px; font-weight: bold; margin: 8px 0 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .section-title { font-size: 11px; font-weight: bold; margin: 14px 0 6px; text-transform: uppercase; border-bottom: 1.5px solid #1e3a5f; padding-bottom: 3px; color: #1e3a5f; }
        .dim-title { font-size: 11px; font-weight: bold; margin: 12px 0 4px; background: #eef2f7; padding: 5px 7px; border-left: 3px solid #1e3a5f; }
        .dim-meta { color: #444; font-size: 9.5px; margin: 2px 0 8px; font-style: italic; }
        .row { margin-bottom: 4px; }
        .label { font-weight: bold; }
        .field-label { font-weight: bold; font-size: 9.5px; margin-top: 6px; color: #222; }
        .check { display: inline-block; margin-right: 8px; white-space: nowrap; }
        .box { border: 1px solid #ccc; padding: 6px 7px; margin: 2px 0 6px; min-height: 22px; text-align: justify; }
        .muted { color: #444; font-size: 9.5px; margin: 3px 0 6px; text-align: justify; }
        .lines { margin: 6px 0 12px; }
        .line { border-bottom: 1px solid #999; height: 16px; margin-bottom: 5px; }
        .sign { margin-top: 16px; }
        .sign-item { margin-bottom: 12px; }
        .footer { margin-top: 14px; font-size: 8.5px; color: #666; text-align: center; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.info td { vertical-align: top; padding: 2px 4px 2px 0; }
        table.obj { width: 100%; border-collapse: collapse; margin: 6px 0 10px; }
        table.obj th, table.obj td { border: 1px solid #888; padding: 4px 5px; vertical-align: top; font-size: 9px; }
        table.obj th { background: #f3f4f6; text-align: left; font-size: 8.5px; }
        .plan-card { border: 1px solid #bbb; padding: 7px 8px; margin: 0 0 10px; page-break-inside: avoid; }
        .plan-card .plan-title { font-weight: bold; font-size: 10px; margin-bottom: 4px; color: #1e3a5f; }
        .plan-card .plan-row { margin-bottom: 3px; }
        .plan-card .plan-key { font-weight: bold; }
    </style>
</head>
<body>
@php
    $especialistasLista = [
        'Neurologista', 'Psiquiatra', 'Psicólogo', 'Fonoaudiólogo', 'T.O.',
        'Psicomotricista', 'Nutricionista', 'ABA', 'Psicopedagogia ou Neuropsicopedagogia', 'Musicoterapia',
    ];
    $marcados = collect($ai['especialistas'] ?? [])->map(fn ($e) => mb_strtolower(trim($e)))->all();
    $sexo = strtoupper((string) ($ai['sexo'] ?? ''));
    $nl = fn ($text) => nl2br(e($text ?: 'Não informado nos documentos enviados.'));
    $txt = fn ($text) => $text ?: 'Não informado nos documentos enviados.';

    $d1 = $ai['dimensao_1'] ?? [];
    $d2 = $ai['dimensao_2'] ?? [];
    $d3 = $ai['dimensao_3'] ?? [];
    $d4 = $ai['dimensao_4'] ?? [];
    $d5 = $ai['dimensao_5'] ?? [];
    $instrumentos = $d4['instrumentos'] ?? [];
    $objetivos = $d3['objetivos'] ?? [];
    $planos = $d5['planos'] ?? [];

    $camposD1 = [
        'identificacao' => 'Identificação',
        'historico_desenvolvimento' => 'Histórico do desenvolvimento',
        'diagnostico_laudos' => 'Diagnóstico e laudos',
        'rede_atendimento' => 'Rede de atendimento',
        'contexto_familiar' => 'Contexto familiar',
        'interesses_preferencias' => 'Interesses e preferências',
        'potencialidades' => 'Potencialidades',
        'desafios' => 'Desafios',
        'perfil_sensorial' => 'Perfil sensorial',
        'comunicacao' => 'Comunicação',
        'saude' => 'Saúde',
        'medicamentos' => 'Medicamentos',
        'rotina' => 'Rotina',
    ];

    $camposD2 = [
        'estilo_aprendizagem' => 'Estilo de aprendizagem',
        'atencao' => 'Atenção',
        'motivacao' => 'Motivação',
        'comunicacao' => 'Comunicação',
        'linguagem' => 'Linguagem',
        'socializacao' => 'Socialização',
        'brincadeiras' => 'Brincadeiras',
        'cognicao' => 'Cognição',
        'funcoes_executivas' => 'Funções executivas',
        'motricidade' => 'Motricidade',
        'autonomia' => 'Autonomia',
        'barreiras_aprendizagem' => 'Barreiras para aprendizagem',
        'recursos_facilitadores' => 'Recursos que facilitam',
    ];

    $camposInstr = [
        'checklists' => 'Checklists',
        'rubricas' => 'Rubricas',
        'registros_fotograficos' => 'Registros fotográficos',
        'videos' => 'Vídeos',
        'portfolio' => 'Portfólio',
        'relatorios' => 'Relatórios',
        'escalas_desenvolvimento' => 'Escalas de desenvolvimento',
        'reunioes_familia' => 'Reuniões com a família',
        'revisoes_pei' => 'Revisões do PEI',
    ];
@endphp

@include('documents.partials.school-header', [
    'headerTitle' => $ai['titulo'] ?? 'PLANEJAMENTO EDUCACIONAL INDIVIDUALIZADO (PEI)',
])

{{-- Cabeçalho --}}
<div class="section-title">Informações do aluno</div>
<table class="info">
    <tr>
        <td style="width:65%"><span class="label">Nome completo do aluno:</span> {{ $student->full_name }}</td>
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
<div class="row" style="margin-top:4px;">
    @foreach($especialistasLista as $esp)
        @php
            $marcado = collect($marcados)->contains(fn ($m) => str_contains($m, mb_strtolower($esp)) || str_contains(mb_strtolower($esp), $m));
        @endphp
        <span class="check">({{ $marcado ? 'x' : '  ' }}) {{ $esp }}</span>
    @endforeach
</div>
<div class="row" style="margin-top:4px;">
    <span class="label">Outros:</span> {{ $ai['outros_especialistas'] ?? '___________________________________________' }}
    &nbsp;&nbsp;({{ !empty($ai['sem_intervencao_terapeutica']) ? 'x' : '  ' }}) Sem Intervenção Terapêutica até o momento.
</div>

<table class="info" style="margin-top:8px;">
    <tr>
        <td><span class="label">Data de início:</span> {{ $ai['data_inicio'] ?? '____/____/______' }}</td>
        <td><span class="label">Data de término:</span> {{ $ai['data_termino'] ?? '____/____/______' }}</td>
        <td><span class="label">Tempo previsto:</span> {{ $ai['tempo_previsto_bimestral'] ?? '____________' }}</td>
    </tr>
</table>

{{-- 1ª Dimensão --}}
<div class="dim-title">1ª Dimensão — Quem é a criança?</div>
<div class="dim-meta">
    Objetivo: {{ $txt($d1['objetivo'] ?? 'Conhecer a criança além do diagnóstico.') }}
    <br>Pergunta central: {{ $txt($d1['pergunta_central'] ?? 'Quem é essa criança e quais fatores influenciam sua aprendizagem?') }}
</div>
@foreach($camposD1 as $key => $label)
    <div class="field-label">{{ $label }}</div>
    <div class="box">{!! $nl($d1[$key] ?? '') !!}</div>
@endforeach

{{-- 2ª Dimensão --}}
<div class="dim-title">2ª Dimensão — Como ela aprende?</div>
<div class="dim-meta">
    Objetivo: {{ $txt($d2['objetivo'] ?? 'Compreender a melhor forma de ensinar.') }}
    <br>Pergunta central: {{ $txt($d2['pergunta_central'] ?? 'O que favorece e o que dificulta sua aprendizagem?') }}
</div>
@foreach($camposD2 as $key => $label)
    <div class="field-label">{{ $label }}</div>
    <div class="box">{!! $nl($d2[$key] ?? '') !!}</div>
@endforeach

{{-- 3ª Dimensão --}}
<div class="dim-title">3ª Dimensão — O que ela precisa desenvolver?</div>
<div class="dim-meta">
    Objetivo: {{ $txt($d3['objetivo'] ?? 'Definir metas claras.') }}
    <br>Pergunta central: {{ $txt($d3['pergunta_central'] ?? 'Quais habilidades queremos desenvolver durante este período?') }}
</div>

@if(count($objetivos) > 0)
    <table class="obj">
        <thead>
            <tr>
                <th style="width:14%">Área</th>
                <th style="width:18%">Habilidade atual</th>
                <th style="width:18%">Habilidade esperada</th>
                <th style="width:10%">Prazo</th>
                <th style="width:20%">Indicador de sucesso</th>
                <th style="width:20%">Forma de avaliação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($objetivos as $obj)
                <tr>
                    <td>{{ $txt($obj['area'] ?? '') }}</td>
                    <td>{{ $txt($obj['habilidade_atual'] ?? '') }}</td>
                    <td>{{ $txt($obj['habilidade_esperada'] ?? '') }}</td>
                    <td>{{ $txt($obj['prazo'] ?? '') }}</td>
                    <td>{{ $txt($obj['indicador_sucesso'] ?? '') }}</td>
                    <td>{{ $txt($obj['forma_avaliacao'] ?? '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="box">Nenhum objetivo informado.</div>
@endif

{{-- 4ª Dimensão --}}
<div class="dim-title">4ª Dimensão — Como acompanhar a evolução?</div>
<div class="dim-meta">
    Objetivo: {{ $txt($d4['objetivo'] ?? 'Monitorar o progresso continuamente.') }}
    <br>Pergunta central: {{ $txt($d4['pergunta_central'] ?? 'Como saberemos que a criança está evoluindo?') }}
</div>

<div class="field-label">Instrumentos de acompanhamento</div>
@foreach($camposInstr as $key => $label)
    <div class="field-label">{{ $label }}</div>
    <div class="box">{!! $nl($instrumentos[$key] ?? '') !!}</div>
@endforeach

<div class="field-label">O que evoluiu?</div>
<div class="box">{!! $nl($d4['o_que_evoluiu'] ?? '') !!}</div>
<div class="field-label">O que não evoluiu?</div>
<div class="box">{!! $nl($d4['o_que_nao_evoluiu'] ?? '') !!}</div>
<div class="field-label">Quais estratégias funcionaram?</div>
<div class="box">{!! $nl($d4['estrategias_funcionaram'] ?? '') !!}</div>
<div class="field-label">O que precisa ser modificado?</div>
<div class="box">{!! $nl($d4['o_que_modificar'] ?? '') !!}</div>

{{-- 5ª Dimensão --}}
<div class="dim-title">5ª Dimensão — Como aplicar? (Plano de Intervenção Pedagógica)</div>
<div class="dim-meta">
    Objetivo: {{ $txt($d5['objetivo'] ?? 'Transformar cada objetivo em ações concretas.') }}
    <br>Pergunta central: {{ $txt($d5['pergunta_central'] ?? 'Como aplicar o plano no dia a dia?') }}
</div>

@forelse($planos as $i => $plano)
    <div class="plan-card">
        <div class="plan-title">Intervenção {{ $i + 1 }} — {{ $txt($plano['objetivo'] ?? 'Objetivo') }}</div>
        <div class="plan-row"><span class="plan-key">O que será ensinado:</span> {{ $txt($plano['o_que_sera_ensinado'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Como ensinar:</span> {{ $txt($plano['como_ensinar'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Em quais momentos:</span> {{ $txt($plano['em_quais_momentos'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Materiais:</span> {{ $txt($plano['materiais'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Adaptação:</span> {{ $txt($plano['adaptacao'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Papel do professor:</span> {{ $txt($plano['papel_professor'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Envolvimento da família:</span> {{ $txt($plano['envolvimento_familia'] ?? '') }}</div>
        <div class="plan-row"><span class="plan-key">Frequência:</span> {{ $txt($plano['frequencia'] ?? '') }}
            @if(!empty($plano['tempo'])) — <span class="plan-key">Tempo:</span> {{ $plano['tempo'] }}@endif
        </div>
        <div class="plan-row"><span class="plan-key">Avaliação:</span> {{ $txt($plano['avaliacao'] ?? '') }}</div>
    </div>
@empty
    <div class="box">Nenhum plano de intervenção informado.</div>
@endforelse

{{-- Observações --}}
<div class="section-title">Leitura do PEI pelos profissionais e equipe multidisciplinar</div>
<p class="muted">(Observações feitas pelos profissionais que atendem o aluno sobre o PEI)</p>

<div class="row"><span class="label">DIREÇÃO/COORDENAÇÃO ESCOLAR:</span></div>
<div class="lines">
    @if(!empty($ai['obs_direcao']))
        <div class="box">{!! $nl($ai['obs_direcao']) !!}</div>
    @else
        @for($i = 0; $i < 3; $i++)<div class="line"></div>@endfor
    @endif
</div>

<div class="row"><span class="label">FAMILIARES E/OU RESPONSÁVEIS:</span></div>
<div class="lines">
    @if(!empty($ai['obs_familiares']))
        <div class="box">{!! $nl($ai['obs_familiares']) !!}</div>
    @else
        @for($i = 0; $i < 3; $i++)<div class="line"></div>@endfor
    @endif
</div>

<div class="row"><span class="label">EQUIPE MULTIDISCIPLINAR:</span></div>
<div class="lines">
    @if(!empty($ai['obs_equipe_multidisciplinar']))
        <div class="box">{!! $nl($ai['obs_equipe_multidisciplinar']) !!}</div>
    @else
        @for($i = 0; $i < 4; $i++)<div class="line"></div>@endfor
    @endif
</div>

<div class="sign">
    <div class="sign-item">Assinatura do professor: ________________________________________________________</div>
    <div class="sign-item">Assinatura da coordenação/direção: ______________________________________________</div>
    <div class="sign-item">Assinatura do responsável: _________________________________________________</div>
    <div class="sign-item">Data: ____/____/________</div>
</div>

<div class="footer">
    Documento gerado em {{ $generated_at }} — PLANNIA
    @if(!empty($ai['_fallback']))
        <br>Observação: conteúdo gerado em modo parcial.
    @endif
</div>
</body>
</html>
