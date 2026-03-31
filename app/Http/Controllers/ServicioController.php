<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // \Log::info('ServicioController@index called', [
        //     'user' => auth()->user()?->id,
        //     'request_params' => $request->all()
        // ]);

        $query = Servicio::query()
            ->with(['usuario', 'equipo', 'encuestasServicio'])
            ->orderByDesc('created_at')
            ->when($request->status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($request->servicio, function ($q, $servicio) {
                return $q->where('servicio', $servicio);
            })
            ->when($request->mantenimiento, function ($q, $mantenimiento) {
                return $q->where('mantenimiento', $mantenimiento);
            });

        // Log de query SQL para debug
        // \Log::info('Servicio query SQL', ['sql' => $query->toSql()]);

        // Si pide paginación, la devuelve paginada
        if ($request->paginate !== 'false') {
            $servicios = $query->paginate(50);
            // \Log::info('Servicios paginados', ['count' => $servicios->total()]);
            return response()->json($servicios);
        }

        // Si no, devuelve array simple
        $servicios = $query->get();
        // \Log::info('Servicios array simple', ['count' => $servicios->count()]);
        
        return response()->json($servicios);
    }

    public function list(): JsonResponse
    {
        $servicios = Servicio::query()
            ->with(['usuario', 'equipo', 'encuestasServicio'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($servicios);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'usuario_id' => 'nullable|exists:users,id',
            'equipo_id' => 'nullable|exists:products,id',
            'cliente_data' => 'nullable|array',
            'cliente_data.nombre' => 'nullable|string|max:255',
            'cliente_data.telefono' => 'nullable|string|max:255',
            'cliente_data.email' => 'nullable|email|max:255',
            'cliente_data.direccion' => 'nullable|string',
            'cliente_data.ciudad' => 'nullable|string|max:255',
            'cliente_data.responsable' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'autorizacion' => 'nullable|string|max:255',
            'servicio' => 'nullable|string|max:255',
            'mantenimiento' => 'nullable|string|max:255',
            'condicion' => 'nullable|string|max:255',
            'actividad' => 'nullable|string',
            'folio' => 'nullable|string|max:255',
            'Nfolio' => 'nullable|string|max:255',
            'visita' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|max:255',
            'salida' => 'nullable|date',
            'fechas' => 'nullable|array',
            'conceptos' => 'nullable|array',
            'ciclos' => 'nullable|array',
            'frio' => 'nullable|array',
            'tipoactividades' => 'nullable|array',
            'boton_deshabilitado' => 'nullable|boolean',
            'procesando_accion' => 'nullable|boolean',
        ]);

        $servicio = DB::transaction(function () use ($validated) {
            $servicio = Servicio::create($validated);

            if (!empty($validated['usuario_id'])) {
                $usuario = User::query()->lockForUpdate()->find($validated['usuario_id']);

                if ($usuario) {
                    $usuario->lastfolio = ((int) ($usuario->lastfolio ?? 0)) + 1;
                    $usuario->save();
                }
            }

            return $servicio;
        });

        $servicio->load(['usuario', 'equipo', 'encuestasServicio']);

        return response()->json($servicio, 201);
    }

    public function show(Servicio $servicio): JsonResponse
    {
        $servicio->load(['usuario', 'equipo', 'encuestasServicio']);
        return response()->json($servicio);
    }

    public function update(Request $request, Servicio $servicio): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'usuario_id' => 'sometimes|nullable|exists:users,id',
            'equipo_id' => 'sometimes|nullable|exists:products,id',
            'cliente_data' => 'sometimes|nullable|array',
            'cliente_data.nombre' => 'nullable|string|max:255',
            'cliente_data.telefono' => 'nullable|string|max:255',
            'cliente_data.email' => 'nullable|email|max:255',
            'cliente_data.direccion' => 'nullable|string',
            'cliente_data.ciudad' => 'nullable|string|max:255',
            'cliente_data.responsable' => 'nullable|string|max:255',
            'status' => 'sometimes|nullable|string|max:255',
            'autorizacion' => 'sometimes|nullable|string|max:255',
            'servicio' => 'sometimes|nullable|string|max:255',
            'mantenimiento' => 'sometimes|nullable|string|max:255',
            'condicion' => 'sometimes|nullable|string|max:255',
            'actividad' => 'sometimes|nullable|string',
            'folio' => 'sometimes|nullable|string|max:255',
            'Nfolio' => 'sometimes|nullable|string|max:255',
            'visita' => 'sometimes|nullable|string|max:255',
            'tipo' => 'sometimes|nullable|string|max:255',
            'salida' => 'sometimes|nullable|date',
            'fechas' => 'sometimes|nullable|array',
            'conceptos' => 'sometimes|nullable|array',
            'ciclos' => 'sometimes|nullable|array',
            'frio' => 'sometimes|nullable|array',
            'tipoactividades' => 'sometimes|nullable|array',
            'boton_deshabilitado' => 'sometimes|nullable|boolean',
            'procesando_accion' => 'sometimes|nullable|boolean',
        ]);

        $servicio->update($validated);
        $servicio->load(['usuario', 'equipo', 'encuestasServicio']);

        return response()->json($servicio);
    }

    public function destroy(Servicio $servicio): JsonResponse
    {
        $servicio->delete();
        return response()->json(['status' => 'deleted']);
    }
}
