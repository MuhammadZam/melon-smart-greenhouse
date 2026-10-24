@extends('layouts.app')

@section('title', 'Konfigurasi & Audit Mesin Fuzzy Logic')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="ui-page-header flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-[2px] text-xs font-semibold bg-surface text-muted border border-line">
                    <span class="w-1.5 h-1.5 rounded-[2px] bg-surface mr-1.5"></span>
                    Administrasi Mesin Inferensi
                </span>
                <span class="text-xs text-muted">Rule Base: {{ $ruleVersion }}</span>
            </div>
            <h2 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight flex items-center gap-2">
                Konfigurasi &amp; Audit <span class="text-ink">Fuzzy Mamdani</span>
            </h2>
            <p class="text-xs sm:text-sm text-muted mt-0.5">
                Spesifikasi matematis fungsi keanggotaan dan matriks aturan pendukung keputusan iklim mikro greenhouse.
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Toggle Button Form --}}
            <form method="POST" action="{{ route('admin.fuzzy.toggle') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 rounded-[2px] text-xs font-semibold flex items-center gap-2 transition-all
 {{ $isEnabled ? 'bg-accent hover:bg-accent text-current ' : 'bg-danger hover:bg-danger text-current ' }}">
                    <span class="w-2 h-2 rounded-[2px] bg-white {{ $isEnabled ? '' : '' }}"></span>
                    <span>Status Engine: {{ $isEnabled ? 'AKTIF' : 'NON-AKTIF' }}</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Status Banner --}}
    <div class="p-4 rounded-[2px] bg-warning-soft border border-warning/25 flex items-start gap-3 text-warning text-xs">
        <svg class="w-5 h-5 text-warning shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <div class="font-semibold tracking-wide">Label Validasi: {{ $validationStatus }}</div>
            <p class="mt-0.5 text-warning leading-relaxed">
                Aturan Fuzzy Simulasi — Belum Divalidasi Tim Elektro. Konfigurasi ini adalah implementasi provisional perangkat lunak.
                Perubahan pada fungsi keanggotaan dan matriks inferensi harus melalui koordinasi akademis dengan pakar elektro dan agronomi melon.
            </p>
        </div>
    </div>

    {{-- Section 1: Fungsi Keanggotaan Input --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-current flex items-center gap-2">
                <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Fungsi Keanggotaan Variabel Input (Semesta Pembicaraan)
            </h3>
            <span class="text-xs text-muted tabular-nums">4 Parameter Operasional</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($inputs as $key => $var)
            <div class="rounded-[2px] bg-canvas border border-line p-5">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-current">{{ $var['name'] }}</h4>
                        <span class="text-xs tabular-nums text-muted">Field: {{ $key }} ({{ $var['unit'] }})</span>
                    </div>
                    <span class="text-xs tabular-nums px-2 py-0.5 rounded-[2px] bg-surface text-ink border border-line">
                        [{{ $var['min'] }} - {{ $var['max'] }} {{ $var['unit'] }}]
                    </span>
                </div>

                <div class="mt-3.5 space-y-2.5">
                    @foreach($var['sets'] as $setName => $setData)
                    <div class="p-3 rounded-[2px] bg-surface border border-line flex items-center justify-between text-xs">
                        <div>
                            <span class="font-semibold text-ink capitalize">{{ $setName }}</span>
                            <span class="ml-2 text-xs tabular-nums px-1.5 py-0.5 rounded-[2px] bg-surface text-ink">
                                {{ $setData['type'] }}
                            </span>
                        </div>
                        <div class="tabular-nums text-xs text-muted">
                            [{{ implode(', ', $setData['params']) }}]
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Section 2: Fungsi Keanggotaan Output --}}
    <div class="rounded-[2px] bg-canvas border border-line p-5 sm:p-6">
        <div class="flex items-center justify-between border-b border-line pb-4 mb-4">
            <div>
                <h3 class="text-sm sm:text-base font-semibold text-current flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Fungsi Keanggotaan Variabel Output (Greenhouse Condition Score)
                </h3>
                <p class="text-xs text-muted mt-0.5">Semesta pembicaraan [0.0 - 100.0] dengan resolusi diskret &Delta;z = 1.0 (Centroid COG)</p>
            </div>
            <span class="text-xs tabular-nums px-2.5 py-1 rounded-[2px] bg-accent-soft text-accent border border-line">
                Mamdani Defuzzification
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($output['sets'] ?? [] as $setName => $setData)
            <div class="p-4 rounded-[2px] bg-surface border border-line">
                <div class="flex items-center justify-between">
                    <strong class="text-current capitalize">{{ str_replace('_', ' ', $setName) }}</strong>
                    <span class="text-xs tabular-nums px-1.5 py-0.5 rounded-[2px] bg-surface text-ink">
                        {{ $setData['type'] }}
                    </span>
                </div>
                <div class="tabular-nums text-xs text-accent mt-2">
                    Parameter: [{{ implode(', ', $setData['params']) }}]
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Section 3: Matriks Aturan (Rule Base) --}}
    <div class="rounded-[2px] bg-canvas border border-line p-5 sm:p-6">
        <div class="flex items-center justify-between border-b border-line pb-4 mb-4">
            <div>
                <h3 class="text-sm sm:text-base font-semibold text-current flex items-center gap-2">
                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Matriks Aturan IF-THEN (Provisional Rule Base)
                </h3>
                <p class="text-xs text-muted mt-0.5">Total {{ count($rules) }} aturan terkurasi untuk software testing</p>
            </div>
            <span class="text-xs text-ink tabular-nums">
                Operator: <strong>AND (min) &bull; OR (max)</strong>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-line bg-canvas text-muted text-xs font-semibold">
                        <th class="py-3 px-3">ID &amp; Nama Aturan</th>
                        <th class="py-3 px-3">Anteseden (IF)</th>
                        <th class="py-3 px-3">Konsekuen (THEN)</th>
                        <th class="py-3 px-3">Bobot</th>
                        <th class="py-3 px-3">Kesimpulan Agronomis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-ink">
                    @foreach($rules as $rule)
                    <tr class="hover:bg-surface transition-colors">
                        {{-- Rule ID & Name --}}
                        <td class="py-3 px-3 whitespace-nowrap">
                            <span class="tabular-nums font-semibold text-muted mr-1.5">{{ $rule['id'] }}</span>
                            <span class="font-semibold text-current">{{ $rule['name'] }}</span>
                        </td>

                        {{-- Anteseden --}}
                        <td class="py-3 px-3">
                            <div class="tabular-nums text-xs space-y-0.5">
                                @foreach($rule['antecedents'] as $param => $val)
                                    <div><span class="text-muted">{{ $param }}:</span> <strong class="text-muted">{{ $val }}</strong></div>
                                @endforeach
                            </div>
                        </td>

                        {{-- Konsekuen --}}
                        <td class="py-3 px-3 whitespace-nowrap">
                            @php
                                $badge = match($rule['consequent']) {
                                    'baik' => 'bg-accent-soft text-accent border-line',
                                    'perlu_perhatian' => 'bg-warning-soft text-warning border-warning/25',
                                    'buruk' => 'bg-danger-soft text-danger border-danger/25',
                                    default => 'bg-surface text-ink border-line',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold border {{ $badge }}">
                                {{ $rule['consequent'] }}
                            </span>
                        </td>

                        {{-- Bobot --}}
                        <td class="py-3 px-3 tabular-nums text-xs whitespace-nowrap text-ink">
                            {{ number_format($rule['weight'] ?? 1.0, 1) }}
                        </td>

                        {{-- Kesimpulan --}}
                        <td class="py-3 px-3 text-xs text-ink max-w-sm">
                            <div class="line-clamp-2">{{ $rule['conclusion'] }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
