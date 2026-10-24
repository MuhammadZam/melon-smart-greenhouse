@props(['title' => '', 'value' => null, 'unit' => '', 'color' => 'emerald'])

<div {{ $attributes->merge(['class' => 'summary-metric p-4 border-b border-line bg-canvas']) }}>
    <div class="flex items-center justify-between gap-3 mb-3">
        <span class="text-xs font-medium text-muted">{{ $title }}</span>
        @if(isset($icon))<span class="text-muted">{{ $icon }}</span>@endif
    </div>
    @if(!is_null($value))
        <div class="text-3xl font-semibold text-ink tabular-nums tracking-tight">
            {{ $value }}
            @if($unit)<span class="text-xs font-normal text-muted ml-1">{{ $unit }}</span>@endif
        </div>
    @endif
    @if(isset($subtitle))<div class="text-xs text-muted mt-2 flex flex-wrap items-center gap-1">{{ $subtitle }}</div>@endif
    @if(isset($slot) && trim($slot) !== '')<div class="{{ !is_null($value) ? 'mt-2' : '' }}">{{ $slot }}</div>@endif
</div>
