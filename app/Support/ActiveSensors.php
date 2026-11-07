<?php

namespace App\Support;

final class ActiveSensors
{
    // Storage keys deliberately preserve historical columns and alarm snapshots.
    public const STORAGE_KEYS = ['temperature', 'soil_moisture', 'ph'];
    public const LABELS = ['temperature' => 'Suhu Udara', 'soil_moisture' => 'Kelembapan Tanah', 'ph' => 'pH air'];
    public const UNITS = ['temperature' => '°C', 'soil_moisture' => '%', 'ph' => ''];
}
