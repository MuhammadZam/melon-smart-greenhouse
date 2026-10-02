@props(['status' => 'active'])

@php
    $normalized = strtolower(trim($status));
    
    // Default mapping
    $dot = 'bg-slate-400';
    $bg = 'bg-slate-500/10';
    $text = 'text-slate-300';
    $border = 'border-slate-500/20';
    $label = ucfirst($status);

    if ($normalized === 'active' || $normalized === 'aktif') {
        $label = 'Aktif';
        $dot = 'bg-emerald-400';
        $bg = 'bg-emerald-500/10';
        $text = 'text-emerald-300';
        $border = 'border-emerald-500/20';
    } elseif ($normalized === 'harvested' || $normalized === 'sudah dipanen') {
        $label = 'Sudah Dipanen';
        $dot = 'bg-amber-400';
        $bg = 'bg-amber-500/10';
        $text = 'text-amber-300';
        $border = 'border-amber-500/20';
    } elseif ($normalized === 'inactive' || $normalized === 'tidak aktif') {
        $label = 'Tidak Aktif';
        $dot = 'bg-slate-500';
        $bg = 'bg-slate-800';
        $text = 'text-slate-400';
        $border = 'border-slate-700';
    } elseif (str_contains($normalized, 'perlu perhatian')) {
        $label = 'Perlu Perhatian';
        $dot = 'bg-amber-400';
        $bg = 'bg-amber-500/10';
        $text = 'text-amber-300';
        $border = 'border-amber-500/20';
    } elseif (str_contains($normalized, 'tidak baik') || str_contains($normalized, 'kritis')) {
        $label = 'Tidak Baik';
        $dot = 'bg-rose-400';
        $bg = 'bg-rose-500/10';
        $text = 'text-rose-300';
        $border = 'border-rose-500/20';
    } elseif (str_contains($normalized, 'baik') || str_contains($normalized, 'optimal')) {
        $label = 'Baik (Optimal)';
        $dot = 'bg-emerald-400';
        $bg = 'bg-emerald-500/10';
        $text = 'text-emerald-300';
        $border = 'border-emerald-500/20';
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold {$bg} {$text} border {$border}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>
