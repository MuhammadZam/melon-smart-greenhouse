<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FuzzyConfigController extends Controller
{
    /**
     * Halaman Konfigurasi & Audit Mesin Fuzzy Logic.
     * Hak Akses: Administrator Only
     */
    public function index(): View
    {
        $this->ensureAdmin();

        $config = config('fuzzy');
        $inputs = $config['inputs'] ?? [];
        $output = $config['output'] ?? [];
        $rules = $config['rules'] ?? [];
        $categories = $config['categories'] ?? [];
        $ruleVersion = $config['rule_version'] ?? 'v1.0-provisional';
        $validationStatus = $config['validation_status'] ?? 'PROVISIONAL_FOR_SOFTWARE_TESTING';

        $isEnabled = Cache::get('fuzzy_inference_runtime_enabled', $config['enabled'] ?? true);

        return view('admin.fuzzy.config', compact(
            'inputs',
            'output',
            'rules',
            'categories',
            'ruleVersion',
            'validationStatus',
            'isEnabled'
        ));
    }

    /**
     * Toggle status aktifasi inferensi fuzzy pada sistem (Admin Only).
     */
    public function toggle(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $current = Cache::get('fuzzy_inference_runtime_enabled', config('fuzzy.enabled', true));
        $new = !$current;

        Cache::forever('fuzzy_inference_runtime_enabled', $new);
        config(['fuzzy.enabled' => $new]);

        $statusStr = $new ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('admin.fuzzy.config')
            ->with('success', "Mesin inferensi Fuzzy Logic berhasil {$statusStr} secara sistem.");
    }

    /**
     * Validasi otorisasi Admin ketat.
     */
    protected function ensureAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Akses Ditolak: Hanya Administrator yang memiliki wewenang untuk melihat dan mengaudit konfigurasi mesin inferensi fuzzy.');
        }
    }
}
