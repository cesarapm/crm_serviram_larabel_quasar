<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoEquipoController extends Controller
{
    public function index(): JsonResponse
    {
        $tipos = TipoEquipo::query()
            ->orderBy('name')
            ->get();

        return response()->json($tipos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'mantenimiento' => 'nullable|array',
            'mantenimiento.*.name' => 'nullable|string|max:255',
            'mantenimiento.*.orden' => 'nullable|integer|min:0',
            'mantenimiento.*.type' => 'nullable|string|in:booleano,text',
        ]);

        $tipo = TipoEquipo::create($validated);

        return response()->json($tipo, 201);
    }

    public function show(TipoEquipo $tipoEquipo): JsonResponse
    {
        return response()->json($tipoEquipo);
    }

    public function update(Request $request, TipoEquipo $tipoEquipo): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'mantenimiento' => 'sometimes|nullable|array',
            'mantenimiento.*.name' => 'nullable|string|max:255',
            'mantenimiento.*.orden' => 'nullable|integer|min:0',
            'mantenimiento.*.type' => 'nullable|string|in:booleano,text',
        ]);

        $tipoEquipo->update($validated);

        return response()->json($tipoEquipo);
    }

    public function destroy(TipoEquipo $tipoEquipo): JsonResponse
    {
        $tipoEquipo->delete();

        return response()->json(['status' => 'deleted']);
    }
}
