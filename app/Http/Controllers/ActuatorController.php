<?php

namespace App\Http\Controllers;

use App\Models\Actuator;
use App\Models\ActuatorCommandLog;
use App\Services\ActuatorSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ActuatorController — Phase 13
 *
 * Menangani:
 *  - Halaman Kontrol Perangkat (Simulasi Pompa Irigasi & Kipas Exhaust)
 *  - Eksekusi Perintah Simulasi (Khusus Administrator)
 *  - Halaman Riwayat Perintah & Audit Trail
 */
class ActuatorController extends Controller
{
    public function __construct(
        protected ActuatorSimulationService $actuatorService
    ) {}

    /**
     * Halaman Kontrol Perangkat (Simulasi).
     * Dapat dilihat oleh Admin dan Pengamat (tombol kontrol hanya aktif untuk Admin).
     */
    public function index(): View
    {
        $actuators = $this->actuatorService->getAllActuators();
        $recentLogs = ActuatorCommandLog::whereHas('actuator', fn ($q) => $q->where('code', 'PUMP-01'))->with(['actuator', 'user'])
            ->orderByDesc('executed_at')
            ->limit(5)
            ->get();

        $pumpControl = app(\App\Services\Pump\PumpControlService::class);
        $controlReady = $pumpControl->ready();
        $pump = $actuators->first();
        $latestSoil = $controlReady && $pump ? $pumpControl->latestReading($pump) : null;
        $pumpState = $controlReady && $pump ? $pumpControl->statusData($pump) : null;
        $plants = \App\Models\Plant::orderBy('name')->get();
        return view('actuators.index', compact('actuators', 'recentLogs', 'controlReady', 'pump', 'latestSoil', 'plants', 'pumpState'));
    }

    /**
     * Eksekusi perintah kontrol aktuator (Simulasi).
     * Khusus Administrator (Otorisasi multi-lapis: Middleware + Controller Gate + Domain Service).
     */
    public function control(Request $request, Actuator $actuator): RedirectResponse|JsonResponse
    {
        abort_if(Auth::user()->role !== 'admin', 403, 'Akses ditolak: Hanya Administrator yang berwenang mengubah status simulasi aktuator.');

        $request->validate([
            'command' => ['required', 'string', 'in:TURN_ON,TURN_OFF,TOGGLE,turn_on,turn_off,toggle'],
            'notes'   => ['nullable', 'string', 'max:255'],
            'expected_version' => ['nullable', 'integer', 'min:0'],
        ]);

        $command = strtoupper($request->input('command'));
        $notes = $request->input('notes');

        $result = app(\App\Services\Pump\PumpControlService::class)->manualCommand(
            $actuator, $command, Auth::user(), ActuatorCommandLog::SOURCE_WEB_SIMULATION, $notes,
            $request->filled('expected_version') ? (int) $request->input('expected_version') : null
        );
        if (!$result['success']) abort(422, $result['message']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('actuators.index')
            ->with('success', $result['message']);
    }

    public function status(Actuator $actuator): JsonResponse
    {
        abort_unless($actuator->code === 'PUMP-01', 404);
        $control = app(\App\Services\Pump\PumpControlService::class);
        abort_unless($control->ready(), 503, 'Migration kontrol pompa belum diterapkan.');
        return response()->json($control->statusData($actuator))->header('Cache-Control', 'no-store');
    }

    public function mode(Request $request, Actuator $actuator): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['control_mode' => ['required', 'in:MANUAL,AUTO'], 'expected_version' => ['required', 'integer', 'min:0']]);
        $result = app(\App\Services\Pump\PumpControlService::class)->changeMode($actuator, $data['control_mode'], Auth::user(), (int) $data['expected_version']);
        return $request->expectsJson() ? response()->json($result) : redirect()->route('actuators.index')->with('success', $result['message']);
    }

    public function settings(Request $request, Actuator $actuator): RedirectResponse|JsonResponse
    {
        $version = $request->validate(['expected_version' => ['required', 'integer', 'min:0']]);
        $result = app(\App\Services\Pump\PumpControlService::class)->configure($actuator, $request->only(['control_plant_id', 'auto_on_threshold', 'auto_off_threshold', 'max_runtime_seconds', 'min_rest_seconds', 'sensor_max_age_seconds']), Auth::user(), (int) $version['expected_version']);
        return $request->expectsJson() ? response()->json($result) : redirect()->route('actuators.index')->with('success', $result['message']);
    }

    /**
     * Halaman Riwayat Kontrol Perangkat.
     * Dapat diakses oleh Admin dan Pengamat.
     */
    public function history(Request $request): View
    {
        $request->validate([
            'actuator_id'      => ['nullable', 'integer'],
            'command'          => ['nullable', 'string'],
            'execution_status' => ['nullable', 'string'],
            'date_from'        => ['nullable', 'date'],
            'date_to'          => ['nullable', 'date'],
        ]);

        $filters = [
            'actuator_id'      => $request->query('actuator_id', 'all'),
            'command'          => $request->query('command', 'all'),
            'execution_status' => $request->query('execution_status', 'all'),
            'date_from'        => $request->query('date_from'),
            'date_to'          => $request->query('date_to'),
        ];

        $logs = $this->actuatorService->getHistory($filters, 15);
        $actuators = Actuator::where('code', 'PUMP-01')->orderBy('name')->get();

        return view('actuators.history', compact('logs', 'actuators', 'filters'));
    }
}
