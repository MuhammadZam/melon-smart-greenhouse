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
        $recentLogs = ActuatorCommandLog::with(['actuator', 'user'])
            ->orderByDesc('executed_at')
            ->limit(5)
            ->get();

        return view('actuators.index', compact('actuators', 'recentLogs'));
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
        ]);

        $command = strtoupper($request->input('command'));
        $notes = $request->input('notes');

        $result = $this->actuatorService->executeCommand(
            actuator: $actuator,
            command: $command,
            user: Auth::user(),
            source: ActuatorCommandLog::SOURCE_WEB_SIMULATION,
            notes: $notes
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('actuators.index')
            ->with('success', $result['message']);
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
        $actuators = Actuator::orderBy('name')->get();

        return view('actuators.history', compact('logs', 'actuators', 'filters'));
    }
}
