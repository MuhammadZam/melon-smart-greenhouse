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
            'id'                   => $this->id,
            'plant_id'             => $this->plant_id,
            'plant'                => $this->whenLoaded('plant', function () {
                return [
                    'id'       => $this->plant->id,
                    'name'     => $this->plant->name,
                    'location' => $this->plant->location,
                    'status'   => $this->plant->status,
                ];
            }),
            'monitored_at'         => $this->monitored_at?->format('Y-m-d H:i:s'),
            'temperature'          => $this->temperature !== null ? (float) $this->temperature : null,
            'nutrient_temperature' => $this->nutrient_temperature !== null ? (float) $this->nutrient_temperature : null,
            'ph'                   => $this->ph !== null ? (float) $this->ph : null,
            'ec'                   => $this->ec !== null ? (float) $this->ec : null,
            'humidity'             => $this->humidity !== null ? (float) $this->humidity : null,
            'soil_moisture'        => $this->soil_moisture !== null ? (float) $this->soil_moisture : null,
            'light_intensity'      => $this->light_intensity !== null ? (float) $this->light_intensity : null,
            'tank_level'           => $this->tank_level !== null ? (float) $this->tank_level : null,
            'plant_height'         => $this->plant_height !== null ? (float) $this->plant_height : null,
            'canopy_density'       => $this->plant_density !== null ? (float) $this->plant_density : null,
            'plant_density'        => $this->plant_density !== null ? (float) $this->plant_density : null,
            'condition'            => $this->plant_condition,
            'plant_condition'      => $this->plant_condition,
            'leaf_color'           => $this->plant_color,
            'plant_color'          => $this->plant_color,
            'notes'                => $this->notes,
            'source'               => $this->source,
            'device_id'            => $this->device_id,
            'source_identifier'    => $this->source_identifier,
            'fuzzy_score'          => $this->fuzzy_score,
            'fuzzy_label'          => $this->fuzzy_label,
            'created_at'           => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'           => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
