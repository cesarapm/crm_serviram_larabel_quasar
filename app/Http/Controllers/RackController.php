<?php

namespace App\Http\Controllers;

use App\Models\Rack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index(): JsonResponse
    {
        $racks = Rack::with('items:id,codigo,nombre,tipo,stock,rack')
            ->orderBy('nombre')
            ->get()
            ->map(function ($rack) {
                return [
                    'id' => $rack->id,
                    'nombre' => $rack->nombre,
                    'descripcion' => $rack->descripcion,
                    'ubicacion' => $rack->ubicacion,
                    'niveles' => $rack->niveles,
                    'capacidad' => $rack->capacidad,
                    'posiciones_por_nivel' => $rack->posiciones_por_nivel,
                    'capacidad_total' => $rack->capacidad_total,
                    'items_actuales' => $rack->items_actuales,
                    'disponible' => $rack->disponible,
                    'porcentaje_ocupacion' => $rack->porcentaje_ocupacion,
                    'estado' => $rack->estado,
                    'valor_total' => $rack->getValorTotal(),
                    'items_por_tipo' => $rack->getItemsPorTipo(),
                    'items' => $rack->items
                ];
            });

        return response()->json($racks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            Rack::rules(),
            Rack::messages()
        );

        $rack = Rack::create($validated);

        return response()->json(['data' => $rack], 201);
    }

    public function show(Rack $rack): JsonResponse
    {
        $rack->load('items:id,codigo,nombre,tipo,stock,stock_minimo,precio_unitario,rack');
        
        $rackData = [
            'id' => $rack->id,
            'nombre' => $rack->nombre,
            'descripcion' => $rack->descripcion,
            'ubicacion' => $rack->ubicacion,
            'niveles' => $rack->niveles,
            'capacidad' => $rack->capacidad,
            'posiciones_por_nivel' => $rack->posiciones_por_nivel,
            'capacidad_total' => $rack->capacidad_total,
            'items_actuales' => $rack->items_actuales,
            'disponible' => $rack->disponible,
            'porcentaje_ocupacion' => $rack->porcentaje_ocupacion,
            'estado' => $rack->estado,
            'valor_total' => $rack->getValorTotal(),
            'items_por_tipo' => $rack->getItemsPorTipo(),
            'items' => $rack->items,
            'created_at' => $rack->created_at,
            'updated_at' => $rack->updated_at
        ];

        return response()->json(['data' => $rackData]);
    }

    public function update(Request $request, Rack $rack): JsonResponse
    {
        $validated = $request->validate(
            Rack::rules($rack->id),
            Rack::messages()
        );

        $rack->update($validated);

        return response()->json(['data' => $rack]);
    }

    public function destroy(Rack $rack): JsonResponse
    {
        // Verificar si tiene items asignados
        if ($rack->items_actuales > 0) {
            return response()->json([
                'error' => 'No se puede eliminar un rack que tiene items asignados'
            ], 400);
        }

        $rack->delete();

        return response()->json(['message' => 'Rack eliminado correctamente']);
    }

    public function estadisticas(): JsonResponse
    {
        $stats = [
            'total_racks' => Rack::count(),
            'capacidad_total' => Rack::sum(\DB::raw('niveles * posiciones_por_nivel * capacidad')),
            'items_almacenados' => \App\Models\Item::whereNotNull('rack')->count(),
            'valor_total' => \App\Models\Item::whereNotNull('rack')
                ->selectRaw('SUM(stock * precio_unitario) as total')
                ->value('total') ?? 0,
            'ocupacion_promedio' => Rack::selectRaw('AVG(
                    (
                        SELECT COUNT(*) 
                        FROM items 
                        WHERE items.rack = racks.nombre AND items.deleted_at IS NULL
                    ) * 100.0 / (niveles * posiciones_por_nivel * capacidad)
                ) as promedio')
                ->value('promedio') ?? 0,
            'racks_por_estado' => [
                'vacio' => Rack::whereRaw('
                    (SELECT COUNT(*) FROM items WHERE items.rack = racks.nombre AND items.deleted_at IS NULL) = 0
                ')->count(),
                'medio' => Rack::whereRaw('
                    (SELECT COUNT(*) FROM items WHERE items.rack = racks.nombre AND items.deleted_at IS NULL) * 100.0 / (niveles * posiciones_por_nivel * capacidad) BETWEEN 30 AND 69
                ')->count(),
                'casi_lleno' => Rack::whereRaw('
                    (SELECT COUNT(*) FROM items WHERE items.rack = racks.nombre AND items.deleted_at IS NULL) * 100.0 / (niveles * posiciones_por_nivel * capacidad) BETWEEN 70 AND 89
                ')->count(),
                'lleno' => Rack::whereRaw('
                    (SELECT COUNT(*) FROM items WHERE items.rack = racks.nombre AND items.deleted_at IS NULL) * 100.0 / (niveles * posiciones_por_nivel * capacidad) >= 90
                ')->count()
            ]
        ];

        return response()->json($stats);
    }

    public function itemsEnRack(Rack $rack, Request $request): JsonResponse
    {
        $query = $rack->items();

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('buscar')) {
            $termino = $request->buscar;
            $query->where(function ($q) use ($termino) {
                $q->where('codigo', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%");
            });
        }

        $items = $query->orderBy('ubicacion')
            ->orderBy('nombre')
            ->get();

        return response()->json($items);
    }
}