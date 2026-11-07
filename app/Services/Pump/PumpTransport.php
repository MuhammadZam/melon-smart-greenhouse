<?php

namespace App\Services\Pump;

use App\Models\Actuator;

interface PumpTransport
{
    public function apply(Actuator $pump, string $state): void;
}
