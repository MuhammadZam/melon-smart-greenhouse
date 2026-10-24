@extends('layouts.app')

@section('title', 'Kontrol Perangkat (Mode Simulasi)')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{
    confirmModalOpen: false,
    selectedActuator: null,
    targetCommand: '',
    targetState: '',
    commandNotes: '',
    isSubmitting: false,

    openConfirm(actuator, command, resultingState) {
        this.selectedActuator = actuator;
        this.targetCommand = command;
        this.targetState = resultingState;
        this.commandNotes = '';
        this.confirmModalOpen = true;
    },

    submitCommand() {
        this.isSubmitting = true;
        document.getElementById('control-form-' + this.selectedActuator.id).submit();
    }
}">

    {{-- Header & Title --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-line">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                    <span class="w-1.5 h-1.5 rounded-[2px] bg-surface "></span>
                    MODE SIMULASI DATABASE
                </span>
            </div>
            <h1 class="ui-page-title text-xl sm:text-2xl font-semibold text-current tracking-tight flex items-center gap-2.5">
                <span class="p-2 rounded-[2px] bg-accent-soft text-accent border border-line">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </span>
                <span>Kontrol Perangkat Aktuator</span>
            </h1>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Simulasi kontrol operasional pompa irigasi fertigasi dan kipas sirkulasi greenhouse melon.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('actuators.history') }}"
               class="px-3.5 py-2 rounded-[2px] text-xs font-semibold bg-surface hover:bg-surface text-ink border border-line transition-colors flex items-center gap-2">
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Lihat Riwayat Kontrol</span>
            </a>
        </div>
    </div>

    {{-- Strict Simulation Disclaimer Banner --}}
    <div class="p-4 sm:p-5 rounded-[2px] bg-warning-soft border border-warning/25 text-warning text-xs sm:text-sm flex flex-col sm:flex-row items-start sm:items-center gap-3.5 ">
        <div class="p-2 rounded-[2px] bg-warning-soft text-warning shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <div class="space-y-1">
            <div class="font-semibold text-warning">Pemberitahuan Arsitektur Simulasi (Phase 13):</div>
            <p class="text-xs text-warning leading-relaxed">
                Kontrol ini merupakan simulasi perangkat dan tidak mengaktifkan hardware fisik. Perubahan perintah hanya mengubah state logikal di database tanpa mengirimkan sinyal relay, HTTP, atau MQTT ke mikrokontroler ESP32 riil.
            </p>
        </div>
    </div>

    {{-- Grid Kartu Aktuator --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($actuators as $actuator)
            @php
                $isOn = $actuator->isOn();
                $isPump = $actuator->type === \App\Models\Actuator::TYPE_IRRIGATION_PUMP;
            @endphp
            <div class="rounded-[2px] bg-canvas border {{ $isOn ? 'border-line ring-1 ring-accent' : 'border-line' }} overflow-hidden  transition-all duration-150 hover:border-line"
                 id="actuator-card-{{ $actuator->code }}">

                {{-- Header Kartu --}}
                <div class="p-5 border-b border-line bg-canvas flex flex-col sm:flex-row items-start justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-[2px] {{ $isOn ? 'bg-accent-soft text-accent border border-line' : 'bg-surface text-muted border border-line' }} flex items-center justify-center shrink-0 ">
                            @if($isPump)
                                {{-- Icon Pompa Irigasi --}}
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                </svg>
                            @else
                                {{-- Icon Kipas Exhaust --}}
                                <svg class="w-6 h-6 {{ $isOn ? '' : '' }}" style="animation-duration: 3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l2-1m-2 1l2 1m-2 1v-2.5M18 18l-2 1m2-1l-2-1m2 1v-2.5"></path>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-semibold text-current">{{ $actuator->name }}</h2>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <code class="px-2 py-0.5 rounded-[2px] text-xs tabular-nums font-semibold bg-surface text-ink border border-line">
                                    {{ $actuator->code }}
                                </code>
                                <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-warning-soft text-warning border border-warning/25">
                                    Simulasi
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Status Pill --}}
                    <div class="shrink-0 whitespace-nowrap">
                        @if($isOn)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[2px] text-xs font-semibold bg-accent-soft text-accent border border-line">
                                <span class="w-2 h-2 rounded-[2px] bg-accent-soft "></span>
                                AKTIF (ON)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-[2px] text-xs font-semibold bg-surface text-muted border border-line">
                                <span class="w-2 h-2 rounded-[2px] bg-surface"></span>
                                NON-AKTIF (OFF)
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Detail Informasi --}}
                <div class="p-5 space-y-4">
                    <p class="text-xs text-muted leading-relaxed">
                        {{ $actuator->description }}
                    </p>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-[2px] bg-canvas border border-line">
                            <span class="block text-xs  font-semibold text-muted">Lokasi Penempatan</span>
                            <span class="text-ink font-medium truncate block mt-0.5" title="{{ $actuator->location }}">
                                {{ $actuator->location ?: '-' }}
                            </span>
                        </div>

                        <div class="p-3 rounded-[2px] bg-canvas border border-line">
                            <span class="block text-xs  font-semibold text-muted">Terakhir Diperbarui</span>
                            <span class="text-ink font-medium truncate block mt-0.5" title="{{ $actuator->last_changed_at }}">
                                {{ $actuator->last_changed_at ? $actuator->last_changed_at->diffForHumans() : 'Belum pernah' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-3 rounded-[2px] bg-canvas border border-line flex items-center justify-between text-xs">
                        <span class="text-muted">Operator Terakhir:</span>
                        <span class="font-semibold text-ink flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            {{ $actuator->lastUser?->name ?: 'Sistem / Inisialisasi' }}
                        </span>
                    </div>

                    {{-- Kontrol Aksi (Admin Only) --}}
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <div class="pt-3 border-t border-line">
                            <form id="control-form-{{ $actuator->id }}"
                                  action="{{ route('actuators.control', $actuator->id) }}"
                                  method="POST"
                                  class="space-y-3">
                                @csrf
                                <input type="hidden" name="command" :value="targetCommand">
                                <input type="hidden" name="notes" :value="commandNotes">

                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button"
                                            @click="openConfirm({{ json_encode($actuator) }}, 'TURN_ON', 'ON')"
                                            {{ $isOn ? 'disabled' : '' }}
                                            class="w-full py-2.5 px-3 rounded-[2px] text-xs font-semibold transition-all flex items-center justify-center gap-2 {{ $isOn ? 'bg-surface text-muted border border-line cursor-not-allowed' : 'bg-accent hover:bg-accent text-current ' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Nyalakan (ON)</span>
                                    </button>

                                    <button type="button"
                                            @click="openConfirm({{ json_encode($actuator) }}, 'TURN_OFF', 'OFF')"
                                            {{ !$isOn ? 'disabled' : '' }}
                                            class="w-full py-2.5 px-3 rounded-[2px] text-xs font-semibold transition-all flex items-center justify-center gap-2 {{ !$isOn ? 'bg-surface text-muted border border-line cursor-not-allowed' : 'bg-danger hover:bg-danger text-current ' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span>Matikan (OFF)</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        {{-- Read-Only Info for Pengamat --}}
                        <div class="pt-3 border-t border-line">
                            <div class="p-3 rounded-[2px] bg-canvas border border-line text-center">
                                <span class="text-xs text-muted flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Akses Pengamat: Mode Baca (Hanya Administrator yang dapat mengubah status)
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Widget Ringkasan Riwayat Perintah Terkini --}}
    <div class="rounded-[2px] bg-canvas border border-line p-5 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <h3 class="text-sm font-semibold text-current flex items-center gap-2">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Aktivitas Perintah Terakhir</span>
            </h3>
            <a href="{{ route('actuators.history') }}" class="text-xs font-semibold text-accent hover:text-accent transition-colors">
                Lihat Semua &rarr;
            </a>
        </div>

        @if($recentLogs->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-xs  font-semibold text-muted border-b border-line">
                        <tr>
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Perangkat</th>
                            <th class="py-2.5 px-3">Perintah</th>
                            <th class="py-2.5 px-3">Perubahan Status</th>
                            <th class="py-2.5 px-3">Operator</th>
                            <th class="py-2.5 px-3">Hasil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line tabular-nums">
                        @foreach($recentLogs as $log)
                            <tr class="hover:bg-surface">
                                <td class="py-2.5 px-3 text-muted">{{ $log->executed_at->format('d/m/Y H:i:s') }}</td>
                                <td class="py-2.5 px-3 font-sans font-semibold text-current">{{ $log->actuator->name }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-[2px] text-xs font-semibold bg-surface text-ink border border-line">
                                        {{ $log->command }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="text-muted">{{ $log->previous_state }}</span>
                                    <span class="text-muted mx-1">&rarr;</span>
                                    <span class="font-semibold {{ $log->resulting_state === 'ON' ? 'text-accent' : 'text-muted' }}">
                                        {{ $log->resulting_state }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-sans text-ink">{{ $log->user?->name ?: 'Sistem' }}</td>
                                <td class="py-2.5 px-3">
                                    @if($log->execution_status === 'SUCCESS')
                                        <span class="text-accent font-semibold">SUKSES</span>
                                    @else
                                        <span class="text-danger font-semibold">{{ $log->execution_status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-6 text-center text-xs text-muted">
                Belum ada aktivitas perintah kontrol yang tercatat.
            </div>
        @endif
    </div>

    {{-- Modal Konfirmasi Perubahan Status (Alpine.js) --}}
    <div x-show="confirmModalOpen"
         x-focus-scope="confirmModalOpen"
         @keydown.escape.window="if(!isSubmitting) confirmModalOpen = false"
         role="dialog"
         aria-modal="true"
         aria-labelledby="actuator-dialog-title"
         :aria-busy="isSubmitting.toString()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-canvas  flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-canvas border border-line rounded-[2px] max-w-md w-full p-6 space-y-5 "
             @click.away="if(!isSubmitting) confirmModalOpen = false">

            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-[2px] bg-warning-soft text-warning border border-warning/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="actuator-dialog-title" class="text-base font-semibold text-current">Konfirmasi Perintah Simulasi</h3>
                    <p class="text-xs text-muted">Pembaruan status perangkat logikal</p>
                </div>
            </div>

            <div class="space-y-3 p-4 rounded-[2px] bg-canvas border border-line text-xs">
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] gap-3">
                    <span class="text-muted">Perangkat:</span>
                    <span class="font-semibold text-current" x-text="selectedActuator?.name"></span>
                </div>
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] gap-3">
                    <span class="text-muted">Kode Perangkat:</span>
                    <span class="tabular-nums text-ink" x-text="selectedActuator?.code"></span>
                </div>
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] gap-3">
                    <span class="text-muted">Perintah Dijalankan:</span>
                    <span class="tabular-nums font-semibold text-warning" x-text="targetCommand"></span>
                </div>
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] gap-3 items-center border-t border-line pt-2">
                    <span class="text-muted">Perubahan Status:</span>
                    <span class="font-semibold">
                        <span class="text-muted" x-text="selectedActuator?.simulation_state"></span>
                        <span class="text-muted mx-1">&rarr;</span>
                        <span :class="targetState === 'ON' ? 'text-accent' : 'text-danger'" x-text="targetState"></span>
                    </span>
                </div>
            </div>

            <div>
                <label for="modal-command-notes" class="block text-xs font-medium text-ink mb-1.5">Catatan Perintah (Opsional):</label>
                <input type="text"
                       id="modal-command-notes"
                       x-model="commandNotes"
                       placeholder="Misal: Uji coba fertigasi pagi hari..."
                       class="w-full px-3 py-2 rounded-[2px] text-xs bg-canvas border border-line text-current placeholder-muted focus:outline-none focus:border-line">
            </div>

            <div class="text-xs text-muted leading-relaxed bg-surface p-3 rounded-[2px] border border-line">
                <strong>Catatan Simulasi:</strong> Tindakan ini hanya memperbarui database aplikasi dan dicatat pada riwayat audit trail. Mikrokontroler fisik tidak terpengaruh.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        @click="confirmModalOpen = false"
                        :disabled="isSubmitting"
                        class="px-4 py-2 rounded-[2px] text-xs font-semibold bg-surface hover:bg-surface text-ink transition-colors">
                    Batal
                </button>

                <button type="button"
                        @click="submitCommand()"
                        :disabled="isSubmitting"
                        class="px-4 py-2 rounded-[2px] text-xs font-semibold bg-accent hover:bg-accent text-current transition-all flex items-center gap-2">
                    <svg x-show="isSubmitting" class=" -ml-1 mr-1 h-3.5 w-3.5 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Memproses...' : 'Konfirmasi &amp; Eksekusi'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
