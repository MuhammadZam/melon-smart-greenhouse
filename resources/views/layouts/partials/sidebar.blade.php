@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M4 13h6V4H4v9Zm10 7h6V11h-6v9ZM4 20h6v-3H4v3ZM14 7h6V4h-6v3Z'],
        ['label' => 'Monitoring', 'icon' => 'M3 12h4l3-7 4 14 3-7h4', 'children' => [
            ['label' => 'Data Monitoring', 'route' => 'monitorings.index', 'match' => 'monitorings.*'],
            ['label' => 'Grafik Sensor', 'route' => 'charts.index'],
            ['label' => 'Histori Terbaru', 'route' => 'history.index'],
        ]],
        ['label' => 'Tanaman', 'route' => 'plants.index', 'match' => 'plants.*', 'icon' => 'M12 21v-7m0 1C6 15 3 11 3 5c5 0 9 3 9 9m0 1c6 0 9-4 9-10-5 0-9 3-9 9'],
        ['label' => 'Alarm', 'route' => 'alarms.index', 'match' => ['alarms.index', 'alarms.show'], 'icon' => 'M15 17H5l2-3V9a5 5 0 0 1 10 0v5l2 3h-4m0 0a3 3 0 0 1-6 0'],
        ['label' => 'Prakiraan Logistik', 'route' => 'logistics.forecast', 'match' => 'logistics.forecast*', 'icon' => 'M3 3v18h18M6 15l5-5 4 3 5-7'],
        ['label' => 'Pompa Simulasi', 'icon' => 'm13 2-9 12h7l-1 8 10-12h-7l1-8Z', 'children' => [
            ['label' => 'Kontrol Pompa', 'route' => 'actuators.index'],
            ['label' => 'Riwayat Kontrol', 'route' => 'actuators.history'],
        ]],
        ['label' => 'Analisis & Laporan', 'icon' => 'M6 3h9l4 4v14H6V3Zm8 0v5h5M9 12h7m-7 4h7', 'children' => [
            ['label' => 'Analisis Fuzzy', 'route' => 'fuzzy.history', 'match' => 'fuzzy.*'],
            ['label' => 'Laporan Monitoring', 'route' => 'reports.monitoring.index', 'match' => 'reports.monitoring.*'],
        ]],
    ];

    if (auth()->user()->isAdmin()) {
        $items[] = ['label' => 'Pengaturan', 'icon' => 'M4 7h16M4 17h16M8 4v6m8 4v6', 'children' => [
            ['label' => 'Pengguna', 'route' => 'admin.users.index'],
            ['label' => 'Parameter', 'route' => 'admin.parameters.index'],
            ['label' => 'Ambang Alarm', 'route' => 'alarms.thresholds'],
            ['label' => 'Google Sheets', 'route' => 'admin.integrations.google-sheets.index', 'match' => 'admin.integrations.google-sheets.*'],
            ['label' => 'Konfigurasi Fuzzy', 'route' => 'admin.fuzzy.config', 'match' => 'admin.fuzzy.*'],
        ]];
    }

    $isActive = fn (array $item) => request()->routeIs(...(array) ($item['match'] ?? $item['route']));
    $menuRow = 'flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent';
@endphp

<aside
    x-cloak
    id="main-navigation"
    aria-label="Navigasi utama"
    :inert="!desktopNav && !sidebarOpen"
    x-focus-scope="sidebarOpen && !desktopNav"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-canvas transition-transform duration-150 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-5">
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center gap-3 text-sm font-semibold text-ink">
            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-accent-soft text-accent">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20v-8M12 15C6.5 15 4 11 4 5c5.5 0 8 3 8 8m0 2c5.5 0 8-4 8-10-5.5 0-8 3-8 8"/>
                </svg>
            </span>
            Melon Monitor
        </a>
        <button type="button" @click="sidebarOpen = false"
                class="rounded-md p-2 text-muted hover:bg-surface hover:text-ink lg:hidden"
                aria-label="Tutup menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav aria-label="Menu aplikasi" class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($items as $item)
            @php
                $children = $item['children'] ?? [];
                $active = $children ? collect($children)->contains($isActive) : $isActive($item);
                $row = $menuRow.' '.($active ? 'bg-accent-soft text-accent' : 'text-muted hover:bg-surface hover:text-ink');
            @endphp

            @if ($children)
                <details class="group" @if ($active) open @endif>
                    <summary class="{{ $row }} cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span>{{ $item['label'] }}</span>
                        <svg class="ml-auto h-3.5 w-3.5 shrink-0 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </summary>
                    <ul class="my-1 ml-5 space-y-0.5 border-l border-line pl-2">
                        @foreach ($children as $child)
                            @php($childActive = $isActive($child))
                            <li>
                                <a href="{{ route($child['route']) }}" @click="sidebarOpen = false"
                                   @if ($childActive) aria-current="page" @endif
                                   class="block rounded-md px-3 py-2.5 text-xs focus-visible:outline-2 focus-visible:outline-accent {{ $childActive ? 'bg-accent-soft font-semibold text-accent' : 'text-muted hover:bg-surface hover:text-ink' }}">
                                    {{ $child['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @else
                <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
                   @if ($active) aria-current="page" @endif class="{{ $row }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>

<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    aria-hidden="true"
    data-sidebar-backdrop
    class="fixed inset-0 z-40 bg-black/60 lg:hidden"
    style="display: none;"
></div>
