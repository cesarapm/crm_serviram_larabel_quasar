<?php

namespace App\Http\Controllers;

use App\Models\EncuestaServicio;
use App\Models\GmServicio;
use App\Models\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EncuestaServicioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EncuestaServicio::query()
            ->with(['servicio', 'gmServicio'])
            ->orderByDesc('fecha')
            ->when($request->origen, function ($q, $origen) {
                return $q->where('origen', $origen);
            })
            ->when($request->servicio_firebase_id, function ($q, $servicioFirebaseId) {
                return $q->where('servicio_firebase_id', $servicioFirebaseId);
            });

        if ($request->paginate !== 'false') {
            return response()->json($query->paginate(50));
        }

        return response()->json($query->get());
    }

    public function list(): JsonResponse
    {
        $encuestas = EncuestaServicio::query()
            ->with(['servicio', 'gmServicio'])
            ->orderByDesc('fecha')
            ->get();

        return response()->json($encuestas);
    }

    public function store(Request $request): JsonResponse
    {
        Log::info($request->all());
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'origen' => 'nullable|string|in:servicio,gm_servicio',
            'servicio_firebase_id' => 'nullable|string|max:255',
            'servicio_id' => 'nullable|integer|exists:servicios,id',
            'gm_servicio_id' => 'nullable|integer|exists:gm_servicios,id',
            'calificacion' => 'nullable|numeric|min:0|max:5',
            'fecha' => 'nullable|date',
        ]);

        $attributes = $this->resolveServicioRelation($validated);
        $encuesta = EncuestaServicio::create($attributes);
        $encuesta->load(['servicio', 'gmServicio']);

        return response()->json($encuesta, 201);
    }

    public function show(EncuestaServicio $encuestasServicio): JsonResponse
    {
        $encuestasServicio->load(['servicio', 'gmServicio']);
        return response()->json($encuestasServicio);
    }

    public function update(Request $request, EncuestaServicio $encuestasServicio): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'origen' => 'sometimes|nullable|string|in:servicio,gm_servicio',
            'servicio_firebase_id' => 'sometimes|nullable|string|max:255',
            'servicio_id' => 'sometimes|nullable|integer|exists:servicios,id',
            'gm_servicio_id' => 'sometimes|nullable|integer|exists:gm_servicios,id',
            'calificacion' => 'sometimes|nullable|numeric|min:0|max:5',
            'fecha' => 'sometimes|nullable|date',
        ]);

        $attributes = $this->resolveServicioRelation(array_merge([
            'origen' => $encuestasServicio->origen,
            'servicio_firebase_id' => $encuestasServicio->servicio_firebase_id,
            'servicio_id' => $encuestasServicio->servicio_id,
            'gm_servicio_id' => $encuestasServicio->gm_servicio_id,
        ], $validated));

        $encuestasServicio->update($attributes);
        $encuestasServicio->load(['servicio', 'gmServicio']);

        return response()->json($encuestasServicio);
    }

    public function destroy(EncuestaServicio $encuestasServicio): JsonResponse
    {
        $encuestasServicio->delete();
        return response()->json(['status' => 'deleted']);
    }

    private function resolveServicioRelation(array $data): array
    {
        $attributes = $data;
        $attributes['servicio_id'] = null;
        $attributes['gm_servicio_id'] = null;

        if (!empty($data['servicio_id'])) {
            $servicio = Servicio::query()->find($data['servicio_id']);

            $attributes['origen'] = 'servicio';
            $attributes['servicio_id'] = $servicio?->id;
            $attributes['servicio_firebase_id'] =
                $data['servicio_firebase_id']
                ?? $servicio?->firebase_id
                ?? (string) $servicio?->id;

            return $attributes;
        }

        if (!empty($data['gm_servicio_id'])) {
            $gmServicio = GmServicio::query()->find($data['gm_servicio_id']);

            $attributes['origen'] = 'gm_servicio';
            $attributes['gm_servicio_id'] = $gmServicio?->id;
            $attributes['servicio_firebase_id'] =
                $data['servicio_firebase_id']
                ?? $gmServicio?->firebase_id
                ?? (string) $gmServicio?->id;

            return $attributes;
        }

        if (empty($data['servicio_firebase_id'])) {
            throw ValidationException::withMessages([
                'referencia_servicio' => ['Debes enviar servicio_id, gm_servicio_id o servicio_firebase_id.'],
            ]);
        }

        if (($data['origen'] ?? null) === 'servicio') {
            $servicio = Servicio::query()->where('firebase_id', $data['servicio_firebase_id'])->first();
            $attributes['servicio_id'] = $servicio?->id;
            $attributes['gm_servicio_id'] = null;

            return $attributes;
        }

        if (($data['origen'] ?? null) === 'gm_servicio') {
            $gmServicio = GmServicio::query()->where('firebase_id', $data['servicio_firebase_id'])->first();
            $attributes['gm_servicio_id'] = $gmServicio?->id;
            $attributes['servicio_id'] = null;

            return $attributes;
        }

        $servicio = Servicio::query()->where('firebase_id', $data['servicio_firebase_id'])->first();
        $gmServicio = GmServicio::query()->where('firebase_id', $data['servicio_firebase_id'])->first();

        if ($servicio && $gmServicio) {
            throw ValidationException::withMessages([
                'origen' => ['ID ambiguo: existe en servicios y gm_servicios. Especifica origen.'],
            ]);
        }

        if ($servicio) {
            $attributes['origen'] = 'servicio';
            $attributes['servicio_id'] = $servicio->id;
            return $attributes;
        }

        if ($gmServicio) {
            $attributes['origen'] = 'gm_servicio';
            $attributes['gm_servicio_id'] = $gmServicio->id;
            return $attributes;
        }

        return $attributes;
    }
}
