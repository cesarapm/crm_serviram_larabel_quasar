<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cotizacion::query()
            ->with(['usuario'])
            ->orderByDesc('created_at')
            ->when($request->folio, function ($q, $folio) {
                return $q->where('folio', 'like', "%{$folio}%");
            })
            ->when($request->compania, function ($q, $compania) {
                return $q->where('compania', 'like', "%{$compania}%");
            })
            ->when($request->usuario_id, function ($q, $usuarioId) {
                return $q->where('usuario_id', $usuarioId);
            });

        if ($request->paginate !== 'false') {
            return response()->json($query->paginate(50));
        }

        return response()->json($query->get());
    }

    public function list(): JsonResponse
    {
        $cotizaciones = Cotizacion::query()
            ->with(['usuario'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($cotizaciones);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'usuario_id' => 'nullable|exists:users,id',
            'usuario_data' => 'nullable|array',
            'contacto' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'terminos' => 'nullable|string',
            'pago' => 'nullable|string',
            'folio_servicio' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'conceptos' => 'nullable|array',
            'tiempo' => 'nullable|string|max:255',
            'fechas' => 'nullable|array',
            'salida' => 'nullable|date',
            'compania' => 'nullable|string|max:255',
            'folio' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
            'Nfolio' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'trabajo' => 'nullable|string',
            'moneda' => 'nullable|string|max:10',
            'boton_deshabilitado' => 'nullable|boolean',
            'procesando_accion' => 'nullable|boolean',
        ]);

        // $cotizacion = Cotizacion::create($validated);


       $cotizacion = DB::transaction(function () use ($validated) {
            $cotizacion = Cotizacion::create($validated);

            if (!empty($validated['usuario_id'])) {
                $usuario = User::query()->lockForUpdate()->find($validated['usuario_id']);

                if ($usuario) {
                    $usuario->Cfolio = ((int) ($usuario->Cfolio ?? 0)) + 1;
                    $usuario->save();
                }
            }

            return $cotizacion;
        });
        
        $cotizacion->load(['usuario']);

        return response()->json($cotizacion, 201);
    }

    public function show(Cotizacion $cotizacion): JsonResponse
    {
        $cotizacion->load(['usuario']);
        return response()->json($cotizacion);
    }

    public function update(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'usuario_id' => 'sometimes|nullable|exists:users,id',
            'usuario_data' => 'sometimes|nullable|array',
            'contacto' => 'sometimes|nullable|string|max:255',
            'ciudad' => 'sometimes|nullable|string|max:255',
            'terminos' => 'sometimes|nullable|string',
            'pago' => 'sometimes|nullable|string',
            'folio_servicio' => 'sometimes|nullable|string|max:255',
            'telefono' => 'sometimes|nullable|string|max:255',
            'conceptos' => 'sometimes|nullable|array',
            'tiempo' => 'sometimes|nullable|string|max:255',
            'fechas' => 'sometimes|nullable|array',
            'salida' => 'sometimes|nullable|date',
            'compania' => 'sometimes|nullable|string|max:255',
            'folio' => 'sometimes|nullable|string|max:255',
            'direccion' => 'sometimes|nullable|string',
            'Nfolio' => 'sometimes|nullable|string|max:255',
            'area' => 'sometimes|nullable|string|max:255',
            'trabajo' => 'sometimes|nullable|string',
            'moneda' => 'sometimes|nullable|string|max:10',
            'boton_deshabilitado' => 'sometimes|nullable|boolean',
            'procesando_accion' => 'sometimes|nullable|boolean',
        ]);

        $cotizacion->update($validated);
        $cotizacion->load(['usuario']);

        return response()->json($cotizacion);
    }

    public function destroy(Cotizacion $cotizacion): JsonResponse
    {
        $cotizacion->delete();
        return response()->json(['status' => 'deleted']);
    }
}