@extends('layouts.app')
@section('title', 'Kontrol Pompa - Melon Monitoring System')
@section('header', 'Kontrol Perangkat (Simulasi)')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-line pb-4">
        <div>
            <h1 class="text-xl font-semibold">Kontrol Perangkat Aktuator — Pompa Air</h1>
            <p class="text-sm text-muted mt-1">PUMP-01 · Manual / Otomatis · SIMULATION ONLY</p>
        </div>
        <a href="{{ route('actuators.history') }}" class="text-sm text-accent underline underline-offset-4">Riwayat Perintah &amp; Audit Trail</a>
    </div>
    <p class="text-sm text-muted border border-line rounded-[4px] p-4">Kontrol ini merupakan simulasi perangkat dan tidak mengaktifkan hardware fisik. Perubahan hanya disimpan di database; belum ada pengiriman perintah ke ESP32.</p>
    @if(!auth()->user()->isAdmin())
        <div class="p-3 border border-line rounded-[4px] text-xs text-muted flex items-center justify-between">
            <span>Akses Pengamat: Mode Baca</span>
            <span>Hanya administrator yang dapat mengubah mode atau saklar pompa.</span>
        </div>
    @endif
    @if($errors->any())
        <div role="alert" class="p-3 border border-danger/25 text-danger rounded-[4px] text-sm">{{ $errors->first() }}</div>
    @endif
    @if(!$pump)
        <p class="p-4 border border-line rounded-[4px]">PUMP-01 belum terdaftar.</p>
    @elseif(!$controlReady)
        <div role="status" class="p-4 border border-line rounded-[4px] space-y-2">
            <p>Migration dual mode belum diterapkan. Persetujuan penerapan skema diperlukan sebelum kontrol baru digunakan.</p>
            <p class="text-sm text-muted">Status simulasi tersimpan: {{ $pump->simulation_state }}. Tidak ada perubahan database proyek dari halaman ini.</p>
        </div>
    @else
    <section x-data="pumpControlPanel(@js($pumpState), @js(route('actuators.status', $pump)))" class="space-y-5" aria-label="Kontrol PUMP-01">
        <div role="alert" x-cloak x-show="error" x-text="error" class="p-3 border border-danger/25 text-danger text-sm rounded-[4px]"></div>
        <div x-show="!state.worker_recent" role="status" class="p-3 border border-warning/25 text-warning text-sm rounded-[4px]">Loop kontrol belum terdeteksi atau terlambat. Jalankan scheduler agar AUTO, batas runtime, dan pemeriksaan sensor berjalan. Pengaman waktu memerlukan worker aktif.</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4" aria-live="polite">
            <div class="border border-line rounded-[4px] p-4 space-y-2">
                <h2 class="text-xs font-semibold text-muted">Status simulasi pompa</h2>
                <p class="text-2xl font-semibold tabular-nums" x-text="state.simulation_state">{{ $pump->simulation_state }}</p>
                <p class="text-xs text-muted">{{ $pump->name }}</p>
                <p class="text-xs text-muted">Mode aktif: <strong x-text="state.control_mode">{{ $pump->control_mode }}</strong></p>
            </div>
            <div class="border border-line rounded-[4px] p-4 space-y-2">
                <h2 class="text-xs font-semibold text-muted">Kelembapan tanah terakhir</h2>
                <p class="text-2xl font-semibold tabular-nums" x-text="state.soil_moisture === null ? 'Belum tersedia' : state.soil_moisture + '%'">{{ $latestSoil?->soil_moisture ?? 'Belum tersedia' }}</p>
                <p class="text-xs text-muted break-words" x-text="state.monitored_at || 'Pilih tanaman pada konfigurasi.'"></p>
                <p class="text-xs text-muted">Data mengikuti tanaman yang dipilih, bukan gabungan semua tanaman.</p>
            </div>
            <div class="border border-line rounded-[4px] p-4 space-y-2">
                <h2 class="text-xs font-semibold text-muted">Status dilaporkan perangkat</h2>
                <p class="font-semibold" x-text="state.reported_pump_state || 'Belum dilaporkan'"></p>
                <p class="text-xs text-muted">Belum terverifikasi fisik. Terpisah dari status simulasi.</p>
                <p class="text-xs text-muted">Tidak ada dispatch hardware.</p>
            </div>
        </div>
        <div class="border border-line rounded-[4px] p-4 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <form action="{{ route('actuators.mode', $pump) }}" method="POST" @submit.prevent="send($el)" class="space-y-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="expected_version" :value="state.control_version" value="{{ $pump->control_version }}">
                    <label for="pump-mode" class="text-sm font-semibold block">Mode pengoperasian</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <select id="pump-mode" name="control_mode" @disabled(!auth()->user()->isAdmin()) :disabled="busy || !connected || {{ auth()->user()->isAdmin() ? 'false' : 'true' }}" class="p-2 border border-line bg-canvas rounded-[4px] text-sm min-h-10">
                            <option value="MANUAL" @selected($pump->control_mode === 'MANUAL')>Manual</option>
                            <option value="AUTO" @selected($pump->control_mode === 'AUTO')>Otomatis</option>
                        </select>
                        @if(auth()->user()->isAdmin())
                        <button type="submit" :disabled="busy || !connected" class="px-3 py-2 min-h-10 border border-line rounded-[4px] text-sm disabled:opacity-50 transition-colors duration-150">Terapkan mode</button>
                        @endif
                    </div>
                </form>
                <form action="{{ route('actuators.control', $pump) }}" method="POST" @submit.prevent="send($el)" class="space-y-2">
                    @csrf
                    <input type="hidden" name="expected_version" :value="state.control_version" value="{{ $pump->control_version }}">
                    <input type="hidden" name="command" :value="state.simulation_state === 'ON' ? 'TURN_OFF' : 'TURN_ON'" value="{{ $pump->isOn() ? 'TURN_OFF' : 'TURN_ON' }}">
                    <span id="manual-switch-label" class="text-sm font-semibold block">Saklar manual ON / OFF</span>
                    <div class="flex items-center gap-3">
                        <button type="submit" role="switch" aria-labelledby="manual-switch-label" aria-checked="{{ $pump->isOn() ? 'true' : 'false' }}" :aria-checked="(state.simulation_state === 'ON').toString()" @disabled(!auth()->user()->isAdmin() || $pump->control_mode === 'AUTO' || !$pump->is_enabled) :disabled="busy || !connected || state.control_mode !== 'MANUAL' || !state.is_enabled || {{ auth()->user()->isAdmin() ? 'false' : 'true' }}" class="relative w-16 h-9 border border-line rounded-[4px] shrink-0 transition-colors duration-150 disabled:opacity-40 disabled:cursor-not-allowed focus-visible:outline-2 focus-visible:outline-accent" :class="state.simulation_state === 'ON' ? 'bg-accent' : 'bg-surface'">
                            <span class="absolute top-1 left-1 w-7 h-7 bg-white border border-line rounded-[2px] transition-transform duration-150 motion-reduce:transition-none" :class="state.simulation_state === 'ON' ? 'translate-x-7' : ''" aria-hidden="true"></span>
                        </button>
                        <span class="text-sm font-semibold" x-text="state.simulation_state"></span>
                    </div>
                    <p class="text-xs text-muted" x-text="state.control_mode === 'AUTO' ? 'AUTO memegang kontrol; saklar manual dinonaktifkan.' : 'Hanya administrator yang dapat mengubah status.'"></p>
                </form>
            </div>
            <div class="border-t border-line pt-3 text-sm" aria-live="polite"><span class="text-muted">Alasan terakhir:</span> <span x-text="state.reason">{{ $pumpState['reason'] }}</span></div>
            <p class="text-xs text-muted">Saat mode atau konfigurasi berubah, pompa kembali OFF. AUTO menunggu pembacaan baru. Batas runtime dan istirahat tetap berlaku di mode manual.</p>
        </div>
        <details class="border border-line rounded-[4px] p-4" @if(!$pump->control_plant_id) open @endif>
            <summary class="text-sm font-semibold cursor-pointer">Konfigurasi AUTO &amp; pengaman · PROVISIONAL</summary>
            <p class="text-xs text-muted mt-3">Belum disahkan tim Elektro. Contoh uji: ON 40%, OFF 60%; bukan threshold agronomis final. Mengubah konfigurasi mematikan pompa dan mengharuskan pembacaan baru.</p>
            <form action="{{ route('actuators.settings', $pump) }}" method="POST" @submit.prevent="send($el)" class="mt-4 space-y-4">
                @csrf @method('PATCH')
                <input type="hidden" name="expected_version" value="{{ $pump->control_version }}">
                <fieldset @disabled(!auth()->user()->isAdmin()) :disabled="busy || !connected || {{ auth()->user()->isAdmin() ? 'false' : 'true' }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <label class="text-xs font-semibold space-y-1">Tanaman / lokasi sensor
                        <select name="control_plant_id" required class="block w-full min-h-10 p-2 border border-line rounded-[4px] bg-canvas text-sm">
                            <option value="">Pilih tanaman</option>
                            @foreach($plants as $plant)<option value="{{ $plant->id }}" @selected(old('control_plant_id', $pump->control_plant_id) == $plant->id)>{{ $plant->name }}</option>@endforeach
                        </select>
                    </label>
                    @foreach(['auto_on_threshold' => ['Ambang ON (%)', '0', '100', '0.01', '40'], 'auto_off_threshold' => ['Ambang OFF (%)', '0', '100', '0.01', '60'], 'max_runtime_seconds' => ['Maksimum menyala (detik)', '1', '3600', '1', '120'], 'min_rest_seconds' => ['Istirahat minimum (detik)', '1', '3600', '1', '60'], 'sensor_max_age_seconds' => ['Usia maksimum sensor (detik)', '1', '3600', '1', '120']] as $key => $field)
                    <label class="text-xs font-semibold space-y-1">{{ $field[0] }}
                        <input name="{{ $key }}" type="number" required min="{{ $field[1] }}" max="{{ $field[2] }}" step="{{ $field[3] }}" placeholder="{{ $field[4] }}" value="{{ old($key, $pump->{$key}) }}" class="block w-full min-h-10 p-2 border border-line rounded-[4px] bg-canvas text-sm">
                    </label>
                    @endforeach
                </fieldset>
                @if(auth()->user()->isAdmin())<button type="submit" :disabled="busy || !connected" class="px-3 py-2 border border-line rounded-[4px] text-sm transition-colors duration-150 disabled:opacity-50">Simpan konfigurasi provisional</button>@else<p class="text-xs text-muted">Mode pengamat: konfigurasi dan kontrol hanya dapat diubah administrator.</p>@endif
            </form>
        </details>
    </section>
    @endif
    <section class="border-t border-line pt-4 space-y-3">
        <h2 class="text-sm font-semibold">Riwayat perintah terbaru</h2>
        @forelse($recentLogs as $log)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs border-b border-line pb-3">
            <span>{{ $log->executed_at->format('d M Y H:i:s') }} · {{ $log->user?->name ?? 'Sistem' }}</span>
            <span>{{ $log->command }} · {{ $log->previous_state }} → {{ $log->resulting_state }} @if($log->previous_mode) · {{ $log->previous_mode }} → {{ $log->resulting_mode }} @endif</span>
            <span class="break-words">{{ $log->reason_code ?? $log->notes }} · {{ $log->source }}</span>
        </div>
        @empty<p class="text-sm text-muted">Belum ada riwayat perintah.</p>@endforelse
    </section>
</div>
@endsection
