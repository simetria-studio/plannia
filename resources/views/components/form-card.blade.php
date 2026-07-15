@props(['title', 'icon' => null])

<div {{ $attributes->merge(['class' => 'plannia-card']) }}>
    <div class="flex items-center gap-2 border-b border-plannia-border px-6 py-4">
        @if($icon)
            <span class="text-plannia-blue">{!! $icon !!}</span>
        @endif
        <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
    </div>
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
