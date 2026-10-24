@props(['status' => 'active'])

@php
    $normalized = strtolower(trim($status));

    // Default mapping
    $dot = 'bg-surface';
    $bg = 'bg-surface';
    $text = 'text-ink';
    $border = 'border-line';
    $label = ucfirst($status);

    if ($normalized === 'active' || $normalized === 'aktif') {
        $label = 'Aktif';
        $dot = 'bg-accent-soft';
        $bg = 'bg-accent-soft';
        $text = 'text-accent';
        $border = 'border-line';
    } elseif ($normalized === 'harvested' || $normalized === 'sudah dipanen') {
        $label = 'Sudah Dipanen';
        $dot = 'bg-surface';
        $bg = 'bg-warning-soft';
        $text = 'text-warning';
        $border = 'border-warning/25';
    } elseif ($normalized === 'inactive' || $normalized === 'tidak aktif') {
        $label = 'Tidak Aktif';
        $dot = 'bg-surface';
        $bg = 'bg-surface';
        $text = 'text-muted';
        $border = 'border-line';
    } elseif (str_contains($normalized, 'perlu perhatian')) {
        $label = 'Perlu Perhatian';
        $dot = 'bg-surface';
        $bg = 'bg-warning-soft';
        $text = 'text-warning';
        $border = 'border-warning/25';
    } elseif (str_contains($normalized, 'tidak baik') || str_contains($normalized, 'kritis')) {
        $label = 'Tidak Baik';
        $dot = 'bg-surface';
        $bg = 'bg-danger-soft';
        $text = 'text-danger';
        $border = 'border-danger/25';
    } elseif (str_contains($normalized, 'baik') || str_contains($normalized, 'optimal')) {
        $label = 'Baik (Optimal)';
        $dot = 'bg-accent-soft';
        $bg = 'bg-accent-soft';
        $text = 'text-accent';
        $border = 'border-line';
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[2px] text-xs font-semibold {$bg} {$text} border {$border}"]) }}>
    <span class="w-1.5 h-1.5 rounded-[2px] {{ $dot }}"></span>
    {{ $label }}
</span>
