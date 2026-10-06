<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plant_id' => $this->plant_id,
            'plant' => $this->whenLoaded('plant', function () {
                return [
                    'id' => $this->plant->id,
                    'name' => $this->plant->name,
                    'location' => $this->plant->location,
                    'status' => $this->plant->status,
                ];
            }),
            'monitored_at' => $this->monitored_at?->format('Y-m-d H:i:s'),
            'temperature' => (float) $this->temperature,
            'ph' => (float) $this->ph,
            'humidity' => (float) $this->humidity,
            'plant_height' => (float) $this->plant_height,
            'canopy_density' => (float) $this->plant_density,
            'plant_density' => (float) $this->plant_density,
            'condition' => $this->plant_condition,
            'plant_condition' => $this->plant_condition,
            'leaf_color' => $this->plant_color,
            'plant_color' => $this->plant_color,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
