<?php

namespace App\Console\Commands;

use App\Services\Pump\PumpControlService;
use Illuminate\Console\Command;

class EvaluatePumpControl extends Command
{
    protected $signature = 'pump:evaluate';
    protected $description = 'Evaluate PUMP-01 simulation control and runtime/stale-data safeguards';

    public function handle(PumpControlService $control): int
    {
        if (!$control->ready()) {
            $this->warn('Pump control schema pending approval/deployment; no control executed.');
            return self::FAILURE;
        }
        $result = $control->tick();
        $this->info($result ? $result['message'] : 'No pump state/reason change. SIMULATION ONLY.');
        return self::SUCCESS;
    }
}
