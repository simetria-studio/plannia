@props(['breadcrumb' => null, 'title' => null, 'subtitle' => null, 'backUrl' => null, 'backLabel' => 'Voltar'])

@if($breadcrumb || $title)
    <div class="mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                @if($breadcrumb)
                    <nav class="text-sm text-gray-500 mb-2">{!! $breadcrumb !!}</nav>
                @endif
                @if($title)
                    <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
                @endif
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>
            @if($backUrl)
                <a href="{{ $backUrl }}" class="plannia-btn-secondary shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ $backLabel }}
                </a>
            @endif
        </div>
    </div>
@endif
