@php
    $headerTitle = $headerTitle ?? null;
    $logoSrc = $school->logoDataUri();
@endphp

@if($logoSrc)
    <div class="logo-wrap">
        <img src="{{ $logoSrc }}" class="logo" width="90" height="90">
    </div>
@endif
<div class="school">{{ $school->name }}</div>
@if($school->cnpj || $school->phone || $school->inep || $school->address)
    <div class="school-meta">
        @if($school->cnpj)
            <span>CNPJ: {{ $school->cnpj }}</span>
        @endif
        @if($school->inep)
            <span>INEP: {{ $school->inep }}</span>
        @endif
        @if($school->phone)
            <span>Tel.: {{ $school->phone }}</span>
        @endif
        @if($school->address)
            <span>{{ $school->address }}</span>
        @endif
    </div>
@endif
@if($headerTitle)
    <div class="doc-title">{{ $headerTitle }}</div>
@endif
