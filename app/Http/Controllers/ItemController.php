<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Item::with(['rackRelacion', 'ultimoMovimiento']);

        // Filtros
        if ($request->filled('tipo')) {
            $query->porTipo($request->tipo);
        }

        if ($request->filled('rack')) {
            $query->porRack($request->rack);
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->boolean('bajo_stock')) {
            $query->bajoStock();
        }

        // Ordenamiento
        $query->orderBy($request->get('orden_por', 'nombre'))
              ->orderBy('codigo');

        $items = $query->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            Item::rules(),
            Item::messages()
        );

        $item = Item::create($validated);

        return response()->json($item, 201);
    }

    public function show(Item $item): JsonResponse
    {
        return response()->json($item);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate(
            Item::rules($item->id),
            Item::messages()
        );

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json(['message' => 'Item eliminado correctamente']);
    }

    public function buscar(Request $request): JsonResponse
    {
        $termino = $request->get('q', '');
        $limite = $request->get('limite', 10);

        $items = Item::buscar($termino)
            ->orderBy('nombre')
            ->limit($limite)
            ->get(['id', 'codigo', 'nombre', 'tipo', 'stock', 'ubicacion']);

        return response()->json($items);
    }

    public function estadisticas(): JsonResponse
    {
        $stats = [
            'total_items' => Item::count(),
            'tipos_count' => Item::selectRaw('tipo, count(*) as cantidad')
                ->groupBy('tipo')
                ->get(),
            'bajo_stock' => Item::bajoStock()->count(),
            'sin_stock' => Item::where('stock', 0)->count(),
            'valor_total' => Item::selectRaw('SUM(stock * precio_unitario) as total')
                ->value('total') ?? 0,
            'por_rack' => Item::selectRaw('rack, count(*) as cantidad, SUM(stock * precio_unitario) as valor')
                ->whereNotNull('rack')
                ->groupBy('rack')
                ->orderBy('rack')
                ->get()
        ];

        return response()->json($stats);
    }

    public function racks(): JsonResponse
    {
        $racks = \App\Models\Rack::with('items:id,codigo,nombre,rack')
            ->get()
            ->map(function ($rack) {
                return [
                    'id' => $rack->id,
                    'nombre' => $rack->nombre,
                    'descripcion' => $rack->descripcion,
                    'ubicacion' => $rack->ubicacion,
                    'niveles' => $rack->niveles,
                    'capacidad_total' => $rack->capacidad_total,
                    'items_actuales' => $rack->items_actuales,
                    'porcentaje_ocupacion' => $rack->porcentaje_ocupacion,
                    'estado' => $rack->estado,
                    'valor_total' => $rack->getValorTotal(),
                    'items' => $rack->items
                ];
            });

        return response()->json($racks);
    }

    public function ajustarStock(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => 'required|in:entrada,salida,ajuste',
            'cantidad' => 'required|integer|min:1',
            'observaciones' => 'nullable|string|max:255'
        ]);

        try {
            $item->ajustarStock(
                $validated['tipo'], 
                $validated['cantidad'], 
                $validated['observaciones'],
                auth()->id()
            );

            return response()->json([
                'item' => $item->fresh(['ultimoMovimiento', 'rackRelacion']),
                'message' => 'Stock ajustado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function historialMovimientos(Item $item): JsonResponse
    {
        $movimientos = $item->movimientos()
            ->with('user:id,name')
            ->paginate(20);

        return response()->json($movimientos);
    }
}