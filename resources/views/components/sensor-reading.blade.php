@props(['value' => null, 'precision' => 1, 'unit' => ''])
<div class="text-2xl font-semibold tabular-nums text-ink tracking-tight flex items-baseline gap-1.5">
    @if($value !== null)
        <span>{{ number_format($value, $precision, ',', '.') }}</span>
        @if($unit)<span class="text-sm font-normal text-muted">{{ $unit }}</span>@endif
    @else
        <span>N/A</span>
    @endif
</div>
