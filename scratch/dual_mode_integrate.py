from pathlib import Path
p=Path('app/Services/ActuatorSimulationService.php');s=p.read_text(encoding='utf-8');a=s.index('        // 1. Validasi Hak Akses');b=s.index('    /**\n     * Ambil seluruh daftar',a)
s=s[:a]+'''        $result = app(\\App\\Services\\Pump\\PumpControlService::class)->manualCommand($actuator, $command, $user, $source, $notes);
        if (!$result['success']) throw new HttpException(422, $result['message']);
        return $result;
    }

'''+s[b:];p.write_text(s,encoding='utf-8')
p=Path('app/Models/Actuator.php');s=p.read_text(encoding='utf-8').replace("        'code',","        'control_mode', 'control_plant_id', 'auto_on_threshold', 'auto_off_threshold',\n        'max_runtime_seconds', 'min_rest_seconds', 'sensor_max_age_seconds',\n        'pump_started_at', 'pump_stopped_at', 'auto_armed_at', 'auto_after_monitoring_id',\n        'control_reason', 'control_version',\n        'code',",1).replace("            'is_enabled'", "            'auto_on_threshold' => 'float', 'auto_off_threshold' => 'float',\n            'max_runtime_seconds' => 'integer', 'min_rest_seconds' => 'integer', 'sensor_max_age_seconds' => 'integer',\n            'control_version' => 'integer', 'auto_after_monitoring_id' => 'integer',\n            'pump_started_at' => 'datetime', 'pump_stopped_at' => 'datetime', 'auto_armed_at' => 'datetime',\n            'is_enabled'",1);p.write_text(s,encoding='utf-8')
p=Path('app/Models/ActuatorCommandLog.php');s=p.read_text(encoding='utf-8').replace("        'actuator_id',","        'previous_mode', 'resulting_mode', 'reason_code', 'monitoring_id', 'control_snapshot',\n        'actuator_id',",1).replace("            'executed_at'", "            'control_snapshot' => 'array',\n            'executed_at'",1);p.write_text(s,encoding='utf-8')
p=Path('app/Providers/AppServiceProvider.php');s=p.read_text(encoding='utf-8').replace('        //\n', '        $this->app->bind(\\App\\Services\\Pump\\PumpTransport::class, \\App\\Services\\Pump\\SimulationPumpTransport::class);\n',1).replace('        Gate::policy(Monitoring::class, MonitoringPolicy::class);','''        Monitoring::created(function () {
            \\Illuminate\\Support\\Facades\\DB::afterCommit(function () {
                try {
                    app(\\App\\Services\\Pump\\PumpControlService::class)->tick('monitoring_committed');
                } catch (\\Throwable $error) {
                    // Ingestion already committed: never replay or roll back its identity receipt.
                    \\Illuminate\\Support\\Facades\\Log::error('Pump evaluation after monitoring commit failed', ['error' => $error->getMessage()]);
                }
            });
        });
        Gate::policy(Monitoring::class, MonitoringPolicy::class);''');p.write_text(s,encoding='utf-8')
p=Path('routes/console.php');s=p.read_text(encoding='utf-8')+'''
// Required for max runtime and stale-data stops even when no telemetry arrives.
\\Illuminate\\Support\\Facades\\Schedule::command('pump:evaluate')->everyFiveSeconds()->withoutOverlapping();
''';p.write_text(s,encoding='utf-8')
p=Path('app/Http/Controllers/ActuatorController.php');s=p.read_text(encoding='utf-8').replace("        return view('actuators.index', compact('actuators', 'recentLogs'));",'''        $pumpControl = app(\\App\\Services\\Pump\\PumpControlService::class);
        $controlReady = $pumpControl->ready();
        $pump = $actuators->first();
        $latestSoil = $controlReady && $pump ? $pumpControl->latestReading($pump) : null;
        $plants = \\App\\Models\\Plant::orderBy('name')->get();
        return view('actuators.index', compact('actuators', 'recentLogs', 'controlReady', 'pump', 'latestSoil', 'plants'));''')
# New UI commands include a version; legacy callers remain serialized and mode guarded.
s=s.replace("            'notes'   => ['nullable', 'string', 'max:255'],", "            'notes'   => ['nullable', 'string', 'max:255'],\n            'expected_version' => ['nullable', 'integer', 'min:0'],")
a=s.index('        $result = $this->actuatorService->executeCommand(');b=s.index("        if ($request->wantsJson()",a)
s=s[:a]+'''        $result = app(\\App\\Services\\Pump\\PumpControlService::class)->manualCommand(
            $actuator, $command, Auth::user(), ActuatorCommandLog::SOURCE_WEB_SIMULATION, $notes,
            $request->filled('expected_version') ? (int) $request->input('expected_version') : null
        );
        if (!$result['success']) abort(422, $result['message']);

'''+s[b:]
pos=s.index('    /**\n     * Halaman Riwayat Kontrol Perangkat.')
s=s[:pos]+'''    public function mode(Request $request, Actuator $actuator): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['control_mode' => ['required', 'in:MANUAL,AUTO'], 'expected_version' => ['required', 'integer', 'min:0']]);
        $result = app(\\App\\Services\\Pump\\PumpControlService::class)->changeMode($actuator, $data['control_mode'], Auth::user(), (int) $data['expected_version']);
        return $request->expectsJson() ? response()->json($result) : redirect()->route('actuators.index')->with('success', $result['message']);
    }

    public function settings(Request $request, Actuator $actuator): RedirectResponse|JsonResponse
    {
        $version = $request->validate(['expected_version' => ['required', 'integer', 'min:0']]);
        $result = app(\\App\\Services\\Pump\\PumpControlService::class)->configure($actuator, $request->only(['control_plant_id', 'auto_on_threshold', 'auto_off_threshold', 'max_runtime_seconds', 'min_rest_seconds', 'sensor_max_age_seconds']), Auth::user(), (int) $version['expected_version']);
        return $request->expectsJson() ? response()->json($result) : redirect()->route('actuators.index')->with('success', $result['message']);
    }

'''+s[pos:];p.write_text(s,encoding='utf-8')
p=Path('routes/web.php');s=p.read_text(encoding='utf-8').replace("        Route::post('/actuators/{actuator}/control'", "        Route::patch('/actuators/{actuator}/mode', [ActuatorController::class, 'mode'])->name('actuators.mode');\n        Route::patch('/actuators/{actuator}/settings', [ActuatorController::class, 'settings'])->name('actuators.settings');\n        Route::post('/actuators/{actuator}/control'",1);p.write_text(s,encoding='utf-8')
