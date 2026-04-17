<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\BuzonServicio;
use App\Models\Cliente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log as FacadesLog;



class BuzonServicioController extends Controller
{
    // ─── index ───────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = BuzonServicio::query()
            ->with(['cliente', 'orden', 'agenda', 'tecnico', 'creadoPor'])
            ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
            ->orderByDesc('created_at')
            ->when($request->estatus, fn ($q, $estatus) => $q->where('estatus', $estatus))
            ->when($request->prioridad, fn ($q, $prioridad) => $q->where('prioridad', $prioridad))
            ->when($request->tipo_equipo, fn ($q, $tipo) => $q->where('tipo_equipo', 'like', "%{$tipo}%"))
            ->when($request->fecha_desde, fn ($q, $desde) => $q->where('fecha_solicitada', '>=', $desde))
            ->when($request->fecha_hasta, fn ($q, $hasta) => $q->where('fecha_solicitada', '<=', $hasta))
            ->when($request->buscar, function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('servicio_descripcion', 'like', "%{$buscar}%")
                        ->orWhere('tipo_equipo', 'like', "%{$buscar}%")
                        ->orWhereJsonContains('cliente_data->compania', $buscar)
                        ->orWhereJsonContains('cliente_data->contacto', $buscar);
                });
            });

        if ($request->paginate === 'false') {
            return response()->json($query->get());
        }

        return response()->json($query->paginate(50));
    }

    // ─── store ───────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        // FacadesLog::info('Creando nuevo BuzonServicio', ['request' => $request->all()]);
        $validated = $request->validate([
            'firebase_id'          => 'nullable|string|max:255',
            'cliente_id'           => 'nullable|exists:clientes,id',
            'orden_id'             => 'nullable|exists:ordenes,id',
            'tecnico_id'           => 'nullable|exists:users,id',
            'cliente_data'         => 'nullable|array',
            'servicio_descripcion' => 'nullable|string',
            'tipo_equipo'          => 'nullable|string|max:255',
            'equipo_data'          => 'nullable|array',
            'fecha_solicitada'     => 'nullable|string|max:255',
            'hora_solicitada'     => 'nullable|string|max:20',
            'fechas'               => 'nullable|array',
            'prioridad'            => ['nullable', Rule::in(['alta', 'media', 'baja'])],
            'tecnico_data'         => 'nullable|array',
            'notas'                => 'nullable|string',
            'estatus'              => ['nullable', Rule::in(['nuevo', 'en_revision', 'agendado', 'completado','rechazado'])],
        ]);

        // Resolver cliente_id desde firebase_id del cliente_data
        if (empty($validated['cliente_id']) && !empty($validated['cliente_data']['firebase_id'])) {
            $validated['cliente_id'] = Cliente::where('firebase_id', $validated['cliente_data']['firebase_id'])->value('id');
        }

        // Resolver tecnico_id desde tecnico_data
        if (empty($validated['tecnico_id']) && !empty($validated['tecnico_data'])) {
            $tecnicoData = $validated['tecnico_data'];
            $validated['tecnico_id'] = User::when(
                !empty($tecnicoData['firebase_id']),
                fn ($q) => $q->where('firebase_id', $tecnicoData['firebase_id']),
                fn ($q) => $q->where('email', $tecnicoData['email'] ?? '')
            )->value('id');
        }

        // Asignar creado_por_id desde el usuario autenticado
        $validated['creado_por_id'] = $request->user()?->id;

        $buzon = BuzonServicio::create($validated);
        $buzon->load(['cliente', 'orden', 'agenda', 'tecnico', 'creadoPor']);

        return response()->json($buzon, 201);
    }

    // ─── show ────────────────────────────────────────────────────────────────
    public function show(BuzonServicio $buzon): JsonResponse
    {
        $buzon->load(['cliente', 'orden', 'agenda', 'tecnico', 'creadoPor']);
        return response()->json($buzon);
    }

    // ─── update ──────────────────────────────────────────────────────────────
    public function update(Request $request, BuzonServicio $buzon): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id'          => 'sometimes|nullable|string|max:255',
            'cliente_id'           => 'sometimes|nullable|exists:clientes,id',
            'orden_id'             => 'sometimes|nullable|exists:ordenes,id',
            'tecnico_id'           => 'sometimes|nullable|exists:users,id',
            'cliente_data'         => 'sometimes|nullable|array',
            'servicio_descripcion' => 'sometimes|nullable|string',
            'tipo_equipo'          => 'sometimes|nullable|string|max:255',
            'equipo_data'          => 'sometimes|nullable|array',
            'fecha_solicitada'     => 'sometimes|nullable|string|max:255',
            'hora_solicitada'      => 'sometimes|nullable|string|max:20',
            'fechas'               => 'sometimes|nullable|array',
            'prioridad'            => ['sometimes', 'nullable', Rule::in(['alta', 'media', 'baja'])],
            'tecnico_data'         => 'sometimes|nullable|array',
            'notas'                => 'sometimes|nullable|string',
            'estatus'              => ['sometimes', 'nullable', Rule::in(['nuevo', 'en_revision', 'agendado', 'completado','rechazado'])],
        ]);

        if (array_key_exists('cliente_data', $validated) && empty($validated['cliente_id'])
            && !empty($validated['cliente_data']['firebase_id'])) {
            $validated['cliente_id'] = Cliente::where('firebase_id', $validated['cliente_data']['firebase_id'])->value('id');
        }

        if (array_key_exists('tecnico_data', $validated) && empty($validated['tecnico_id'])
            && !empty($validated['tecnico_data'])) {
            $tecnicoData = $validated['tecnico_data'];
            $validated['tecnico_id'] = User::when(
                !empty($tecnicoData['firebase_id']),
                fn ($q) => $q->where('firebase_id', $tecnicoData['firebase_id']),
                fn ($q) => $q->where('email', $tecnicoData['email'] ?? '')
            )->value('id');
        }

        $buzon->update($validated);
        $buzon->load(['cliente', 'orden', 'agenda', 'tecnico', 'creadoPor']);

        return response()->json($buzon);
    }

    // ─── destroy ─────────────────────────────────────────────────────────────
    public function destroy(BuzonServicio $buzon): JsonResponse
    {
        if ($buzon->estatus === 'agendado') {
            return response()->json([
                'message' => 'No se puede eliminar un buzón que ya fue agendado. Cambia el estatus primero.',
            ], 422);
        }

        $buzon->delete();
        return response()->json(['status' => 'deleted']);
    }

    // ─── agendar ─────────────────────────────────────────────────────────────
    public function agendar(Request $request, BuzonServicio $buzon): JsonResponse
    {
        if ($buzon->estatus === 'agendado') {
            return response()->json(['message' => 'Este requerimiento ya fue agendado.'], 422);
        }
        if ($buzon->estatus === 'completado') {
            return response()->json(['message' => 'No se puede agendar un requerimiento completado.'], 422);
        }

        $extra = $request->validate([
            'start'      => 'nullable|string|max:255',
            'start_raw'  => 'nullable|string|max:255',
            'all_day'    => 'nullable|boolean',
            'text_color' => 'nullable|string|max:50',
            'title'      => 'nullable|string|max:255',
            'block'      => 'nullable|boolean',
        ]);

        $agendaData = DB::transaction(function () use ($buzon, $extra) {
            // Construir start para la agenda combinando fecha + hora si no viene start explícito
            if (!empty($extra['start'])) {
                $startInput = $extra['start'];
            } elseif (!empty($buzon->fecha_solicitada) && !empty($buzon->hora_solicitada)) {
                $startInput = $buzon->fecha_solicitada . ' ' . $buzon->hora_solicitada;
            } else {
                $startInput = $buzon->fecha_solicitada;
            }

            $startRaw = $extra['start_raw'] ?? $startInput;
            $startDb  = null;

            if (!empty($startInput)) {
                try {
                    $startDb = Carbon::parse($startInput)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $startDb = null;
                }
            }

            // Construir título por defecto
            $title = $extra['title']
                ?? ($buzon->cliente_data['compania'] ?? ($buzon->cliente_data['contacto'] ?? 'Sin cliente'))
                . ' - ' . ($buzon->tipo_equipo ?? 'Equipo')
                . ' - ' . ($buzon->servicio_descripcion ? mb_substr($buzon->servicio_descripcion, 0, 60) : 'Servicio');

            $agenda = Agenda::create([
                'orden_id'          => $buzon->orden_id,
                'id_orden_firebase' => $buzon->orden?->firebase_id,
                'start'             => $startDb,
                'start_raw'         => $startRaw,
                'fecha'             => $buzon->fecha_solicitada,
                'all_day'           => $extra['all_day'] ?? false,
                'text_color'        => $extra['text_color'] ?? null,
                'title'             => $title,
                'equipo_data'       => $buzon->equipo_data,
                'block'             => $extra['block'] ?? false,
                'estatus'           => true,
            ]);

            $buzon->update([
                'agenda_id' => $agenda->id,
                'estatus'   => 'agendado',
            ]);

            return $agenda;
        });

        $buzon->load(['cliente', 'orden', 'agenda', 'tecnico', 'creadoPor']);

        return response()->json([
            'buzon'  => $buzon,
            'agenda' => $agendaData,
        ]);
    }

    // ─── cambiarEstatus ──────────────────────────────────────────────────────
    public function cambiarEstatus(Request $request, BuzonServicio $buzon): JsonResponse
    {
        $validated = $request->validate([
            'estatus' => ['required', Rule::in(['nuevo', 'en_revision', 'agendado', 'completado','rechazado'])],
        ]);

        // No permitir revertir desde "agendado" a estados previos
        if ($buzon->estatus === 'agendado' && in_array($validated['estatus'], ['nuevo', 'en_revision'])) {
            return response()->json([
                'message' => 'No se puede revertir un requerimiento ya agendado a un estado anterior.',
            ], 422);
        }

        $buzon->update(['estatus' => $validated['estatus']]);
        $buzon->load(['cliente', 'orden', 'agenda', 'tecnico', 'creadoPor']);

        return response()->json($buzon);
    }

    // ─── alertas ─────────────────────────────────────────────────────────────
    public function alertas(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user    = $request->user();
        $isAdmin = (bool) ($user?->hasRole('admin') ?? false);

        $pendientes = BuzonServicio::query()
            ->with(['cliente', 'orden', 'tecnico', 'creadoPor'])
            ->whereIn('estatus', ['nuevo', 'en_revision'])
            ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
            ->orderBy('created_at')
            ->get()
            ->filter(fn (BuzonServicio $b) => $this->canViewBuzon($b, $user, $isAdmin))
            ->values()
            ->map(function (BuzonServicio $b) {
                $payload = $b->toArray();
                $payload['notificacion'] = [
                    'prioridad' => $b->prioridad,
                    'color'     => $this->colorPorPrioridad($b->prioridad),
                    'nivel'     => $b->prioridad,
                ];
                return $payload;
            });

        return response()->json($pendientes);
    }

    // ─── Helpers privados ────────────────────────────────────────────────────
    private function canViewBuzon(BuzonServicio $buzon, ?User $user, bool $isAdmin): bool
    {
        if ($isAdmin || $user === null) return true;

        // El técnico asignado siempre lo puede ver
        if (!empty($buzon->tecnico_id) && (int) $buzon->tecnico_id === (int) $user->id) return true;

        // Quien lo creó también lo puede ver
        if (!empty($buzon->creado_por_id) && (int) $buzon->creado_por_id === (int) $user->id) return true;

        return false;
    }

    private function colorPorPrioridad(string $prioridad): string
    {
        return match ($prioridad) {
            'alta'  => 'red',
            'media' => 'yellow',
            default => 'green',
        };
    }
}
