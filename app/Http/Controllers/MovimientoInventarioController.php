<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimientoInventarioController extends Controller
{
    /**
     * Obtener todos los movimientos con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        $query = MovimientoInventario::with(['item', 'user']);

        // Filtro por tipo de movimiento
        if ($request->has('tipo')) {
            $query->where('tipo_movimiento', $request->tipo);
        }

        // Filtro por item
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Filtro por rango de fechas
        if ($request->has('fecha_desde')) {
            $query->where(function($q) use ($request) {
                $q->where('fecha', '>=', $request->fecha_desde)
                  ->orWhere(function($subq) use ($request) {
                      $subq->whereNull('fecha')
                           ->where('created_at', '>=', $request->fecha_desde);
                  });
            });
        }

        if ($request->has('fecha_hasta')) {
            $query->where(function($q) use ($request) {
                $q->where('fecha', '<=', $request->fecha_hasta)
                  ->orWhere(function($subq) use ($request) {
                      $subq->whereNull('fecha')
                           ->where('created_at', '<=', $request->fecha_hasta);
                  });
            });
        }

        // Búsqueda en nombre o código del item
        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('item', function($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        // Ordenar por fecha descendente (usar fecha si existe, si no created_at)
        $movimientos = $query->orderByRaw('COALESCE(fecha, created_at) DESC')->get();

        return response()->json(['data' => $movimientos]);
    }

    /**
     * Registrar un nuevo movimiento
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_movimiento' => 'required|in:Entrada,Salida,Préstamo,Devolución,Uso en Servicio,Ajuste',
            'item_id' => 'required|exists:items,id',
            'cantidad' => 'required|integer|min:1',
            'fecha' => 'required|date',
            'responsable' => 'nullable|string|max:255',
            'motivo' => 'nullable|string',
            'referencia' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Obtener el item con bloqueo para evitar race conditions
            $item = Item::lockForUpdate()->findOrFail($validated['item_id']);
            $stockAnterior = $item->stock;

            // Calcular nuevo stock según tipo de movimiento
            switch ($validated['tipo_movimiento']) {
                case 'Entrada':
                case 'Devolución':
                    $stockNuevo = $stockAnterior + $validated['cantidad'];
                    break;

                case 'Salida':
                case 'Préstamo':
                case 'Uso en Servicio':
                    if ($stockAnterior < $validated['cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Stock insuficiente. Stock actual: ' . $stockAnterior
                        ], 422);
                    }
                    $stockNuevo = $stockAnterior - $validated['cantidad'];
                    break;

                case 'Ajuste':
                    $stockNuevo = $validated['cantidad'];
                    break;

                default:
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Tipo de movimiento no válido'
                    ], 422);
            }

            // Actualizar el stock del item
            $item->stock = $stockNuevo;
            $item->save();

            // Crear el registro del movimiento
            $movimiento = MovimientoInventario::create([
                'tipo_movimiento' => $validated['tipo_movimiento'],
                'item_id' => $validated['item_id'],
                'cantidad' => $validated['cantidad'],
                'fecha' => $validated['fecha'],
                'responsable' => $validated['responsable'] ?? null,
                'motivo' => $validated['motivo'] ?? null,
                'referencia' => $validated['referencia'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            // Cargar las relaciones
            $movimiento->load(['item', 'user']);

            return response()->json([
                'data' => $movimiento,
                'message' => 'Movimiento registrado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar el movimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}
