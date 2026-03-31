<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Orden::query()
            ->with(['usuario'])
            ->orderByDesc('created_at')
            ->when($request->folio, fn ($q, $folio) => $q->where('folio', 'like', "%{$folio}%"))
            ->when($request->idfolio, fn ($q, $idfolio) => $q->where('idfolio', 'like', "%{$idfolio}%"))
            ->when($request->servicio, fn ($q, $servicio) => $q->where('servicio', $servicio))
            ->when($request->mantenimiento, fn ($q, $mantenimiento) => $q->where('mantenimiento', $mantenimiento))
            ->when($request->has('estatus'), fn ($q) => $q->where('estatus', filter_var($request->estatus, FILTER_VALIDATE_BOOLEAN)));

        if ($request->paginate !== 'false') {
            return response()->json($query->paginate(50));
        }

        return response()->json($query->get());
    }

    public function list(): JsonResponse
    {
        return response()->json(
            Orden::query()->with(['usuario'])->orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'usuario_id' => 'nullable|exists:users,id',
            'usuario_data' => 'nullable|array',
            'cliente_data' => 'nullable|array',
            'fecha' => 'nullable|string|max:255',
            'fechas' => 'nullable|array',
            'servicio' => 'nullable|string|max:255',
            'mantenimiento' => 'nullable|string|max:255',
            'folio' => 'nullable|string|max:255',
            'idfolio' => 'nullable|string|max:255',
            'estatus' => 'nullable|boolean',
            'equipos' => 'nullable|array',
            'boton_deshabilitado' => 'nullable|boolean',
            'procesando_accion' => 'nullable|boolean',
        ]);

        if (empty($validated['usuario_id']) && !empty($validated['usuario_data']['id'])) {
            $validated['usuario_id'] = User::where('firebase_id', $validated['usuario_data']['id'])->value('id');
        }

        // $orden = Orden::create($validated)
            $orden = DB::transaction(function () use ($validated) {
            $orden = Orden::create($validated);

            if (!empty($validated['usuario_id'])) {
                $usuario = User::query()->lockForUpdate()->find($validated['usuario_id']);

                if ($usuario) {
                    $usuario->Dfolio = ((int) ($usuario->Dfolio ?? 0)) + 1;
                    $usuario->save();
                }
            }

            return $orden;
        });

        $orden->load(['usuario']);

        return response()->json($orden, 201);
    }

    public function show(Orden $orden): JsonResponse
    {
        $orden->load(['usuario', 'agendaEventos']);
        return response()->json($orden);
    }

    public function update(Request $request, Orden $orden): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'usuario_id' => 'sometimes|nullable|exists:users,id',
            'usuario_data' => 'sometimes|nullable|array',
            'cliente_data' => 'sometimes|nullable|array',
            'fecha' => 'sometimes|nullable|string|max:255',
            'fechas' => 'sometimes|nullable|array',
            'servicio' => 'sometimes|nullable|string|max:255',
            'mantenimiento' => 'sometimes|nullable|string|max:255',
            'folio' => 'sometimes|nullable|string|max:255',
            'idfolio' => 'sometimes|nullable|string|max:255',
            'estatus' => 'sometimes|nullable|boolean',
            'equipos' => 'sometimes|nullable|array',
            'boton_deshabilitado' => 'sometimes|nullable|boolean',
            'procesando_accion' => 'sometimes|nullable|boolean',
        ]);

        if (array_key_exists('usuario_data', $validated) && empty($validated['usuario_id']) && !empty($validated['usuario_data']['id'])) {
            $validated['usuario_id'] = User::where('firebase_id', $validated['usuario_data']['id'])->value('id');
        }

        $orden->update($validated);
        $orden->load(['usuario']);

        return response()->json($orden);
    }

    public function destroy(Orden $orden): JsonResponse
    {
        // Eliminar eventos de agenda relacionados si existen
        if (method_exists($orden, 'agendaEventos')) {
            $agendaEventos = $orden->agendaEventos;
            if ($agendaEventos) {
                foreach ($agendaEventos as $evento) {
                    $evento->delete();
                }
            }
        }
        $orden->delete();
        return response()->json(['status' => 'deleted']);
    }
}
