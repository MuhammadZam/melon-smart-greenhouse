@props([
    'title' => '',
    'value' => null,
    'unit' => '',
    'color' => 'emerald',
])

@php
    $colorMap = [
        'emerald' => [
            'border' => 'hover:border-emerald-500/30',
            'iconBg' => 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
            'subText' => 'text-emerald-400/90',
        ],
        'teal' => [
            'border' => 'hover:border-teal-500/30',
            'iconBg' => 'bg-teal-500/10 border-teal-500/20 text-teal-400',
            'subText' => 'text-teal-400/90',
        ],
        'sky' => [
            'border' => 'hover:border-sky-500/30',
            'iconBg' => 'bg-sky-500/10 border-sky-500/20 text-sky-400',
            'subText' => 'text-sky-400/90',
        ],
        'amber' => [
            'border' => 'hover:border-amber-500/30',
            'iconBg' => 'bg-amber-500/10 border-amber-500/20 text-amber-400',
            'subText' => 'text-amber-400/90',
        ],
    ];

    $theme = $colorMap[$color] ?? $colorMap['emerald'];
@endphp

<div {{ $attributes->merge(['class' => "p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-lg relative overflow-hidden group {$theme['border']} transition-all"]) }}>
    <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $title }}</span>
        @if (isset($icon))
            <div class="w-9 h-9 rounded-xl border flex items-center justify-center {{ $theme['iconBg'] }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    @if (!is_null($value))
        <div class="text-3xl font-extrabold text-white tracking-tight">
            {{ $value }}
            @if ($unit)
                <span class="text-xs font-normal text-slate-400 ml-1">{{ $unit }}</span>
            @endif
        </div>
    @endif

    @if (isset($subtitle))
        <div class="text-[11px] {{ $theme['subText'] }} mt-2 flex items-center gap-1">
            {{ $subtitle }}
        </div>
    @endif

    @if (isset($slot) && trim($slot) !== '')
        <div class="{{ !is_null($value) ? 'mt-2' : '' }}">
            {{ $slot }}
        </div>
    @endif
</div>
