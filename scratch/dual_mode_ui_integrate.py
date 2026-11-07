from pathlib import Path
p=Path('app/Models/Actuator.php');s=p.read_text(encoding='utf-8').replace("'pump_started_at', 'pump_stopped_at', 'auto_armed_at',", "'pump_started_at', 'pump_stopped_at', 'auto_armed_at', 'last_evaluated_at',").replace("'pump_started_at' => 'datetime',", "'last_evaluated_at' => 'datetime', 'pump_started_at' => 'datetime',");p.write_text(s,encoding='utf-8')
p=Path('app/Http/Controllers/ActuatorController.php');s=p.read_text(encoding='utf-8').replace("$latestSoil = $controlReady && $pump ? $pumpControl->latestReading($pump) : null;", "$latestSoil = $controlReady && $pump ? $pumpControl->latestReading($pump) : null;\n        $pumpState = $controlReady && $pump ? $pumpControl->statusData($pump) : null;").replace("'latestSoil', 'plants'", "'latestSoil', 'plants', 'pumpState'")
pos=s.index('    public function mode(');s=s[:pos]+'''    public function status(Actuator $actuator): JsonResponse
    {
        abort_unless($actuator->code === 'PUMP-01', 404);
        $control = app(\\App\\Services\\Pump\\PumpControlService::class);
        abort_unless($control->ready(), 503, 'Migration kontrol pompa belum diterapkan.');
        return response()->json($control->statusData($actuator))->header('Cache-Control', 'no-store');
    }

'''+s[pos:];p.write_text(s,encoding='utf-8')
p=Path('routes/web.php');s=p.read_text(encoding='utf-8').replace("    Route::get('/actuators/history'", "    Route::get('/actuators/{actuator}/status', [ActuatorController::class, 'status'])->name('actuators.status');\n    Route::get('/actuators/history'",1);p.write_text(s,encoding='utf-8')
p=Path('resources/js/app.js');s=p.read_text(encoding='utf-8').replace('Alpine.start();', '''Alpine.data('pumpControlPanel', (initial, statusUrl) => ({
    state: initial, busy: false, refreshing: false, connected: true, error: '', timer: null,
    init() { this.timer = setInterval(() => this.refresh(), 5000); },
    destroy() { clearInterval(this.timer); },
    async refresh() {
        if (this.refreshing || this.busy) return;
        this.refreshing = true;
        try {
            const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (!response.ok) throw new Error('Status pompa tidak dapat diperbarui. Muat ulang halaman.');
            this.state = await response.json(); this.connected = true;
        } catch (error) { this.connected = false; this.error = error.message; }
        finally { this.refreshing = false; }
    },
    async send(form) {
        if (this.busy) return;
        const data = new FormData(form); // Capture submit fields before disabling controls.
        this.busy = true; this.error = '';
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, headers: {'Accept': 'application/json'}});
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message || 'Perintah tidak berhasil.');
            window.location.reload();
        } catch (error) { this.error = error.message; this.busy = false; await this.refresh(); }
    },
}));

Alpine.start();''');p.write_text(s,encoding='utf-8')
p=Path('resources/views/actuators/history.blade.php');s=p.read_text(encoding='utf-8').replace('                    <option value="TOGGLE"', '''                    @foreach(['SET_MODE', 'CONFIGURE', 'EVALUATE'] as $command)
                        <option value="{{ $command }}" @selected(($filters['command'] ?? '') === $command)>{{ $command }}</option>
                    @endforeach
                    <option value="TOGGLE"''').replace('                                        {{ $log->command }}','''                                        {{ $log->command }}
                                        @if($log->previous_mode)
                                            <span class="block">{{ $log->previous_mode }} → {{ $log->resulting_mode }}</span>
                                            <span class="block">{{ $log->reason_code }}</span>
                                        @endif''');p.write_text(s,encoding='utf-8')
