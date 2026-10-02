<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\MonitoringParameter;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama untuk pengguna yang telah terautentikasi.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Ringkasan data untuk status dashboard
        $totalPlants       = Plant::count();
        $totalMonitorings  = Monitoring::count();
        $totalParameters   = MonitoringParameter::count();
        $recentMonitorings = Monitoring::with('plant')
            ->latest('monitored_at')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'totalPlants',
            'totalMonitorings',
            'totalParameters',
            'recentMonitorings'
        ));
    }
}
