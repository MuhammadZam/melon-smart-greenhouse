@extends('layouts.app')

@section('title', 'Prakiraan Logistik')

@section('content')
<div class="space-y-6">
    <header class="ui-page-header">
        <h2 class="ui-page-title text-xl sm:text-2xl font-semibold">Prakiraan Logistik</h2>
        <p class="mt-1 text-sm text-muted">Visualisasi prakiraan dari CSV tim logistik. Waktu pada grafik mengikuti waktu yang tercantum di file.</p>
    </header>

    <section class="ui-section space-y-3" aria-labelledby="forecast-upload-title">
        <h3 id="forecast-upload-title" class="text-base font-semibold">Unggah CSV prakiraan</h3>
        <form method="POST" action="{{ route('logistics.forecast.upload') }}" enctype="multipart/form-data"
              class="flex flex-wrap gap-3 items-center">
            @csrf
            <input id="forecast-file" name="forecast" type="file" accept=".csv,text/csv" required
                   aria-labelledby="forecast-upload-title"
                   class="w-full min-w-0 sm:w-auto max-w-full rounded-[2px] border border-line bg-surface p-2 text-sm">
            <button type="submit" class="rounded-[2px] bg-accent px-4 py-2 text-sm font-semibold text-white">Tampilkan grafik</button>
            <a href="{{ route('logistics.forecast', ['contoh' => 1]) }}"
               class="rounded-[2px] border border-line px-4 py-2 text-sm font-semibold text-accent hover:bg-surface">Lihat data contoh</a>
        </form>
        <p class="text-xs text-muted">CSV berkoma, maksimal 2 MB dan 10.000 titik. Gunakan header dan format waktu seperti pada data contoh.</p>
        @if (isset($uploadError) || $errors->has('forecast'))
            <p role="alert" class="rounded-[2px] border border-line bg-danger-soft p-3 text-sm text-danger">{{ $uploadError ?? $errors->first('forecast') }}</p>
        @endif
    </section>

    @isset($forecast)
        <section class="ui-section space-y-4" aria-labelledby="forecast-results-title">
            <div class="min-w-0">
                <h3 id="forecast-results-title" class="text-base font-semibold">Ringkasan prakiraan</h3>
                <p class="mt-1 break-all text-xs text-muted">{{ $filename }}</p>
            </div>
            <dl class="grid gap-3 sm:grid-cols-3">
                <div class="min-w-0 border border-line bg-surface p-3">
                    <dt class="text-xs text-muted">Titik prediksi</dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums">{{ number_format(count($forecast['rows']), 0, ',', '.') }}</dd>
                </div>
                <div class="min-w-0 border border-line bg-surface p-3">
                    <dt class="text-xs text-muted">Periode prediksi</dt>
                    <dd class="mt-1 text-sm font-semibold tabular-nums">{{ $forecast['start'] }} – {{ $forecast['end'] }}</dd>
                </div>
                <div class="min-w-0 border border-line bg-surface p-3">
                    <dt class="text-xs text-muted">Metode</dt>
                    <dd class="mt-1 break-words text-sm font-semibold">{{ $forecast['methods'] }}</dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-4 items-center">
                <label class="flex flex-wrap items-center gap-2 text-sm">
                    Tanggal prediksi
                    <select id="forecast-day" class="border border-line rounded-[2px] bg-canvas px-3 py-2">
                        <option value="">Semua tanggal</option>
                    </select>
                </label>
                @if (collect($forecast['metrics'])->contains('hasMae', true))
                    <label class="flex flex-wrap items-center gap-2 text-sm">
                        Seri grafik
                        <select id="forecast-series" class="border border-line rounded-[2px] bg-canvas px-3 py-2">
                            <option value="forecast">Nilai prakiraan</option>
                            <option value="mae">Galat uji (MAE)</option>
                        </select>
                    </label>
                @endif
            </div>
            <p id="forecast-chart-error" hidden role="alert" class="text-sm text-danger">Grafik tidak dapat dimuat. Coba muat ulang halaman.</p>
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($forecast['metrics'] as $key => $metric)
                    <article data-forecast-panel="{{ $key }}" class="min-w-0 border border-line bg-canvas p-4">
                        <h4 class="text-sm font-semibold">{{ $metric['label'] }} ({{ $metric['unit'] }})</h4>
                        <p data-forecast-summary class="mt-1 text-xs text-muted" aria-live="polite"></p>
                        <div class="relative mt-3 h-56 sm:h-64">
                            <canvas role="img" aria-label="Grafik {{ $metric['label'] }} dalam {{ $metric['unit'] }}"></canvas>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (!window.Chart) {
                        document.getElementById('forecast-chart-error').hidden = false;
                        return;
                    }

                    const forecast = {{ \Illuminate\Support\Js::from($forecast) }};
                    const dayFilter = document.getElementById('forecast-day');
                    const seriesFilter = document.getElementById('forecast-series');
                    const dayKey = time => new Date(time).toISOString().slice(0, 10);
                    const formatTime = new Intl.DateTimeFormat('id-ID', {
                        timeZone: 'UTC', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
                    }).format;
                    const formatValue = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format;
                    const charts = {};

                    for (const day of new Set(forecast.rows.map(row => dayKey(row.time)))) {
                        dayFilter.add(new Option(day.split('-').reverse().join('/'), day));
                    }

                    function render() {
                        const rows = dayFilter.value
                            ? forecast.rows.filter(row => dayKey(row.time) === dayFilter.value)
                            : forecast.rows;
                        const showMae = seriesFilter?.value === 'mae';

                        for (const [key, metric] of Object.entries(forecast.metrics)) {
                            const panel = document.querySelector('[data-forecast-panel="' + key + '"]');
                            panel.hidden = showMae && !metric.hasMae;
                            if (panel.hidden) continue;

                            const column = showMae ? 'MAE_uji_' + key : key;
                            const label = showMae ? 'Galat uji (MAE)' : 'Nilai prakiraan';
                            const values = rows.map(row => row[column]);
                            panel.querySelector('[data-forecast-summary]').textContent =
                                label + ' · Min ' + formatValue(Math.min(...values)) + ' · Maks ' +
                                formatValue(Math.max(...values)) + ' ' + metric.unit;
                            const dataset = {
                                label, data: rows.map(row => ({ x: row.time, y: row[column] })),
                                borderWidth: 2, pointRadius: rows.length === 1 ? 3 : 0,
                                pointHoverRadius: 4, pointHitRadius: 8, borderDash: []
                            };
                            if (charts[key]) {
                                charts[key].data.datasets = [dataset];
                                charts[key].update();
                                continue;
                            }
                            charts[key] = new window.Chart(panel.querySelector('canvas'), {
                                type: 'line',
                                data: { datasets: [dataset] },
                                options: {
                                    responsive: true, maintainAspectRatio: false, parsing: false,
                                    interaction: { mode: 'nearest', axis: 'x', intersect: false },
                                    scales: { x: { type: 'linear', ticks: { callback: value => formatTime(Number(value)) } } },
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { callbacks: {
                                            title: items => formatTime(items[0].parsed.x),
                                            label: item => labelForTooltip(item, metric.unit)
                                        } }
                                    }
                                }
                            });
                        }
                    }

                    function labelForTooltip(item, unit) {
                        return item.dataset.label + ': ' + formatValue(item.parsed.y) + ' ' + unit;
                    }

                    dayFilter.addEventListener('change', render);
                    seriesFilter?.addEventListener('change', render);
                    render();
                });
            </script>
        @endpush
    @endisset
</div>
@endsection
