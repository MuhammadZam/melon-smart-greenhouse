<?php

namespace App\Http\Requests;

trait NormalizesWaterPh
{
    protected function normalizeWaterPh(): void
    {
        if ($this->exists('water_ph')) {
            if ($this->exists('ph') && (!is_numeric($this->input('ph')) || !is_numeric($this->input('water_ph')) || (float) $this->input('ph') !== (float) $this->input('water_ph'))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['water_ph' => 'water_ph dan alias ph harus sama.']);
            }
            $this->merge(['ph' => $this->input('water_ph')]);
        }
    }
}
