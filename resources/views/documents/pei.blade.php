<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; line-height: 1.4; }
        .school { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
        .logo { max-height: 60px; display: block; margin: 0 auto 6px; }
        .doc-title { text-align: center; font-size: 13px; font-weight: bold; margin: 10px 0 14px; text-transform: uppercase; letter-spacing: 0.3px; }
        .section-title { font-size: 11px; font-weight: bold; margin: 14px 0 8px; text-transform: uppercase; border-bottom: 1px solid #333; padding-bottom: 2px; }
        .row { margin-bottom: 5px; }
        .label { font-weight: bold; }
        .check { display: inline-block; margin-right: 8px; white-space: nowrap; }
        .box { border: 1px solid #ccc; padding: 8px; margin: 6px 0 10px; min-height: 40px; text-align: justify; }
        .muted { color: #444; font-size: 10px; margin: 4px 0 8px; text-align: justify; }
        .lines { margin: 6px 0 12px; }
        .line { border-bottom: 1px solid #999; height: 18px; margin-bottom: 6px; }
        .sign { margin-top: 18px; }
        .sign-item { margin-bottom: 14px; }
        .footer { margin-top: 16px; font-size: 9px; color: #666; text-align: center; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.info td { vertical-align: top; padding: 2px 4px 2px 0; }
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
    @endphp

    @if($school->logo_path && file_exists(storage_path('app/public/' . $school->logo_path)))
        <img src="{{ storage_path('app/public/' . $school->logo_path) }}" class="logo">
    @endif
    <div class="school">{{ $school->name }}</div>
    <div class="doc-title">{{ $ai['titulo'] ?? 'PLANEJAMENTO EDUCACIONAL INDIVIDUALIZADO (PEI)' }}</div>

    <div class="section-title">I – Informações do aluno</div>

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

    <div class="section-title">II – Ações necessárias para implementação do PEI</div>
    <p class="muted">
        O PEI é um plano individualizado escrito para planificar (delinear e programar) a proposta educacional
        para o aluno com deficiência, transtornos globais do desenvolvimento e surdez.
    </p>

    <table class="info">
        <tr>
            <td><span class="label">Data de início:</span> {{ $ai['data_inicio'] ?? '____/____/______' }}</td>
            <td><span class="label">Data de término:</span> {{ $ai['data_termino'] ?? '____/____/______' }}</td>
            <td><span class="label">Tempo previsto bimestral:</span> {{ $ai['tempo_previsto_bimestral'] ?? '____________' }}</td>
        </tr>
    </table>

    <div class="row" style="margin-top:10px;"><span class="label">PROCESSO DE ENSINO E APRENDIZAGEM</span></div>
    <p class="muted">
        Indicar as habilidades gerais e as expectativas de aprendizagem individuais pertinentes ao aluno e
        previstos para serem trabalhados para o período estabelecido neste plano.
    </p>
    <div class="box">{!! $nl($ai['processo_ensino_aprendizagem'] ?? '') !!}</div>

    <div class="row"><span class="label">Indicar conteúdos, objetivos e as habilidades a serem desenvolvidas.</span></div>
    <p class="muted">Precisamos nos perguntar: o que o aluno poderá aprender neste período? Que atividades poderão ser desenvolvidas?</p>
    <div class="box">{!! $nl($ai['conteudos_objetivos_habilidades'] ?? '') !!}</div>

    <div class="row"><span class="label">No processo de ensino-aprendizagem serão utilizados os seguintes recursos:</span></div>
    <div class="box">{!! $nl($ai['recursos_ensino'] ?? '') !!}</div>

    <div class="row"><span class="label">Indicar as tecnologias de apoio, as estratégias pedagógicas e as adaptações de materiais e conteúdos.</span></div>
    <p class="muted">
        Precisamos nos questionar quanto às barreiras (arquitetônicas, físicas, cognitivas, sensoriais, comportamentais),
        fazendo com que os limites sejam transpostos e o foco se dê nas possibilidades quanto ao desenvolvimento do processo de ensino-aprendizagem.
    </p>
    <div class="box">{!! $nl($ai['tecnologias_estrategias_adaptacoes'] ?? '') !!}</div>

    <div class="row"><span class="label">Indicar espaços e profissionais envolvidos no PEI.</span></div>
    <p class="muted">
        O PEI é um instrumento para o planejamento colaborativo entre a escola, a equipe multidisciplinar e o aluno.
    </p>
    <div class="box">{!! $nl($ai['espacos_profissionais'] ?? '') !!}</div>

    <div class="row"><span class="label">CRITÉRIOS E INSTRUMENTOS PARA AVALIAÇÃO DESTE PEI</span></div>
    <div class="box">{!! $nl($ai['criterios_instrumentos_avaliacao'] ?? '') !!}</div>

    <div class="row"><span class="label">MUDANÇAS A SEREM CONSIDERADAS PARA A ELABORAÇÃO DO PRÓXIMO PEI</span></div>
    <div class="box">{!! $nl($ai['mudancas_proximo_pei'] ?? '') !!}</div>

    <div class="section-title">III – Leitura do PEI pelos profissionais e equipe multidisciplinar</div>
    <p class="muted">(Observações feitas pelos profissionais que atendem o aluno sobre o PEI)</p>

    <div class="row"><span class="label">DIREÇÃO/COORDENAÇÃO ESCOLAR:</span></div>
    <div class="lines">
        @if(!empty($ai['obs_direcao']))
            <div class="box">{!! $nl($ai['obs_direcao']) !!}</div>
        @else
            @for($i = 0; $i < 4; $i++)<div class="line"></div>@endfor
        @endif
    </div>

    <div class="row"><span class="label">FAMILIARES E/OU RESPONSÁVEIS:</span></div>
    <div class="lines">
        @if(!empty($ai['obs_familiares']))
            <div class="box">{!! $nl($ai['obs_familiares']) !!}</div>
        @else
            @for($i = 0; $i < 4; $i++)<div class="line"></div>@endfor
        @endif
    </div>

    <div class="row"><span class="label">EQUIPE MULTIDISCIPLINAR:</span></div>
    <div class="lines">
        @if(!empty($ai['obs_equipe_multidisciplinar']))
            <div class="box">{!! $nl($ai['obs_equipe_multidisciplinar']) !!}</div>
        @else
            @for($i = 0; $i < 5; $i++)<div class="line"></div>@endfor
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
