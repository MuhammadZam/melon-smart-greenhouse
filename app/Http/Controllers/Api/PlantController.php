<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlantResource;
use App\Models\Plant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    /**
     * Mengambil daftar tanaman melon beserta jumlah observasi dan monitoring terakhir.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'in:active,harvested,inactive,all'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $plants = Plant::withCount('monitorings')
            ->with(['latestMonitoring.plant'])
            ->filter($request->only(['search', 'status']))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data tanaman berhasil diambil.',
            'data' => PlantResource::collection($plants),
        ], 200);
    }

    /**
     * Mengambil detail spesimen tanaman melon beserta histori dan status terkini.
     */
    public function show(Plant $plant): JsonResponse
    {
        $plant->loadCount('monitorings')->load('latestMonitoring.plant');

        return response()->json([
            'success' => true,
            'message' => 'Detail data tanaman berhasil diambil.',
            'data' => new PlantResource($plant),
        ], 200);
    }
}
