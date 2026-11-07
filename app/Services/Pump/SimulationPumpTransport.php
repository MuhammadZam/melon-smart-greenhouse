<?php

namespace App\Services\Pump;

use App\Models\Actuator;

final class SimulationPumpTransport implements PumpTransport
{
    public function apply(Actuator $pump, string $state): void
    {
        // No network, GPIO, MQTT, relay, or physical dispatch.
        $pump->simulation_state = $state;
    }
}
