<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'status' => $this->status,
            'description' => $this->description,
            'monitorings_count' => (int) ($this->monitorings_count ?? $this->monitorings()->count()),
            'latest_monitoring' => $this->relationLoaded('latestMonitoring') && $this->latestMonitoring
                ? new MonitoringResource($this->latestMonitoring)
                : ($this->latestMonitoring()->exists() ? new MonitoringResource($this->latestMonitoring) : null),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
