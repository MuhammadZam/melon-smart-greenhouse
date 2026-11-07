@props(['monitoring'])
@php($configured = \App\Models\MonitoringParameter::whereIn('parameter_key', \App\Support\ActiveSensors::STORAGE_KEYS)->get()->keyBy('parameter_key'))
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    @foreach(\App\Support\ActiveSensors::LABELS as $key => $label)
        @php($threshold = $configured->get($key))
        <div class="p-4 border border-line rounded-[2px] bg-canvas space-y-2">
            <h3 class="text-sm font-semibold">{{ $label }}</h3>
            <x-sensor-reading :value="$monitoring?->{$key}" :precision="$key === 'ph' ? 2 : 1" :unit="\App\Support\ActiveSensors::UNITS[$key]" />
            <p class="text-xs text-muted">Threshold PROVISIONAL:
                @if($threshold)
                    {{ $threshold->minimum_value }} – {{ $threshold->maximum_value }} {{ \App\Support\ActiveSensors::UNITS[$key] }}
                @else
                    Belum dikonfigurasi
                @endif
            </p>
        </div>
    @endforeach
</div>
