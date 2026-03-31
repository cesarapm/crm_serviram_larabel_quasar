<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Orden;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Agenda::query()
            ->with(['orden'])
            ->orderByDesc('start')
            ->when($request->orden_id, fn ($q, $ordenId) => $q->where('orden_id', $ordenId))
            ->when($request->id_orden_firebase, fn ($q, $idOrdenFirebase) => $q->where('id_orden_firebase', $idOrdenFirebase))
            ->when($request->title, fn ($q, $title) => $q->where('title', 'like', "%{$title}%"))
            ->when($request->has('estatus'), fn ($q) => $q->where('estatus', filter_var($request->estatus, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->start_from, fn ($q, $startFrom) => $q->whereDate('start', '>=', $startFrom))
            ->when($request->start_to, fn ($q, $startTo) => $q->whereDate('start', '<=', $startTo));

        if ($request->paginate !== 'false') {
            $paginator = $query->paginate(100);
            $paginator->setCollection(
                $paginator->getCollection()->map(fn (Agenda $agenda) => $this->transformAgendaForFrontend($agenda))
            );

            return response()->json($paginator);
        }

        return response()->json(
            $query->get()->map(fn (Agenda $agenda) => $this->transformAgendaForFrontend($agenda))
        );
    }

    public function list(): JsonResponse
    {
        return response()->json(
            Agenda::query()
                ->with(['orden'])
                ->orderByDesc('start')
                ->get()
                ->map(fn (Agenda $agenda) => $this->transformAgendaForFrontend($agenda))
        );
    }

    public function byOrden(Orden $orden): JsonResponse
    {
        $agenda = Agenda::query()
            ->where('orden_id', $orden->id)
            ->orderBy('start')
            ->get();

        return response()->json(
            $agenda->map(fn (Agenda $item) => $this->transformAgendaForFrontend($item))
        );
    }

    // Alertas virtuales para frontend: no persiste cambios, solo calcula color por dias restantes.
    public function alertas(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $isAdmin = (bool) ($user?->hasRole('admin') ?? false);

        $diasMaximos = max(1, min(30, (int) $request->get('dias_maximos', 5)));
        $hoy = Carbon::today();
        $limite = $hoy->copy()->addDays($diasMaximos);

        $agendas = Agenda::query()
            ->with(['orden'])
            ->whereNotNull('start')
            ->whereDate('start', '>=', $hoy->toDateString())
            ->whereDate('start', '<=', $limite->toDateString())
            ->orderBy('start')
            ->get()
            ->filter(fn (Agenda $agenda) => $this->canViewAgendaAlert($agenda, $user, $isAdmin))
            ->values()
            ->map(function (Agenda $agenda) use ($hoy) {
                $fechaInicio = $this->resolveAgendaDate($agenda);

                if ($fechaInicio === null) {
                    return null;
                }

                $daysLeft = $hoy->diffInDays($fechaInicio->copy()->startOfDay(), false);
                if ($daysLeft < 0) {
                    return null;
                }

                $color = $this->resolveAlertColor($daysLeft);
                if ($color === null) {
                    return null;
                }

                $payload = $agenda->toArray();
                $payload['days_left'] = $daysLeft;
                $payload['text_color'] = $color;
                $payload['textColor'] = $color;
                $payload['notificacion'] = [
                    'dias_restantes' => $daysLeft,
                    'color' => $color,
                    'nivel' => $this->resolveAlertLevel($daysLeft),
                ];

                return $payload;
            })
            ->filter()
            ->values();

        return response()->json(
            $agendas->map(function (array $payload) {
                $payload['allDay'] = (bool) ($payload['all_day'] ?? false);
                $payload['textColor'] = $payload['textColor'] ?? ($payload['text_color'] ?? null);
                $payload['startStr'] = (string) ($payload['start'] ?? ($payload['start_raw'] ?? ''));

                return $payload;
            })->values()
        );
    }

    private function canViewAgendaAlert(Agenda $agenda, ?User $user, bool $isAdmin): bool
    {
        if ($isAdmin || $user === null) {
            return true;
        }

        $orden = $agenda->orden;
        if (!$orden) {
            return false;
        }

        if (!empty($orden->usuario_id) && (int) $orden->usuario_id === (int) $user->id) {
            return true;
        }

        $candidatos = $this->extractOrdenUsuarios($orden->usuario_data);
        foreach ($candidatos as $candidato) {
            if ($this->matchesAgendaUser($candidato, $user)) {
                return true;
            }
        }

        return false;
    }

    private function extractOrdenUsuarios(mixed $usuarioData): array
    {
        if (!is_array($usuarioData)) {
            return [];
        }

        // Caso 1: objeto directo {id, nombre, email, ...}
        if ($this->looksLikeUserPayload($usuarioData)) {
            return [$usuarioData];
        }

        // Caso 2: arreglo indexado/mixto con varios usuarios.
        $usuarios = [];
        foreach ($usuarioData as $entry) {
            if (is_array($entry) && $this->looksLikeUserPayload($entry)) {
                $usuarios[] = $entry;
            }
        }

        return $usuarios;
    }

    private function looksLikeUserPayload(array $payload): bool
    {
        return isset($payload['id'])
            || isset($payload['nickname'])
            || isset($payload['email'])
            || isset($payload['nombre'])
            || isset($payload['name']);
    }

    private function matchesAgendaUser(array $candidato, User $user): bool
    {
        $firebaseId = strtolower(trim((string) ($candidato['id'] ?? '')));
        $userFirebaseId = strtolower(trim((string) ($user->firebase_id ?? '')));
        if ($firebaseId !== '' && $userFirebaseId !== '' && $firebaseId === $userFirebaseId) {
            return true;
        }

        $email = strtolower(trim((string) ($candidato['email'] ?? '')));
        $userEmail = strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '' && $userEmail !== '' && $email === $userEmail) {
            return true;
        }

        $nickname = strtolower(trim((string) ($candidato['nickname'] ?? '')));
        $userNickname = strtolower(trim((string) ($user->nickname ?? '')));
        if ($nickname !== '' && $userNickname !== '' && $nickname === $userNickname) {
            return true;
        }

        $nombre = strtolower(trim((string) ($candidato['nombre'] ?? $candidato['name'] ?? '')));
        $userNombre = strtolower(trim((string) ($user->name ?? '')));
        if ($nombre !== '' && $userNombre !== '' && $nombre === $userNombre) {
            return true;
        }

        return false;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'orden_id' => 'nullable|exists:ordenes,id',
            'id_orden_firebase' => 'nullable|string|max:255',
            'start' => 'nullable|string|max:255',
            'start_raw' => 'nullable|string|max:255',
            'fecha' => 'nullable|string|max:255',
            'all_day' => 'nullable|boolean',
            'text_color' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'equipo_data' => 'nullable|array',
            'block' => 'nullable|boolean',
            'estatus' => 'nullable|boolean',
        ]);

        if (array_key_exists('start', $validated) && !empty($validated['start'])) {
            $startInput = trim((string) $validated['start']);
            if (empty($validated['start_raw'])) {
                $validated['start_raw'] = $startInput;
            }

            $normalizedStart = $this->normalizeStartForDatabase($startInput);
            if ($normalizedStart === null) {
                return response()->json([
                    'message' => 'El campo start no tiene un formato de fecha/hora valido.',
                ], 422);
            }

            $validated['start'] = $normalizedStart;
        }

        if (empty($validated['orden_id']) && !empty($validated['id_orden_firebase'])) {
            $validated['orden_id'] = Orden::where('firebase_id', $validated['id_orden_firebase'])->value('id');
        }

        $agenda = Agenda::create($validated);
        $agenda->load(['orden']);

        return response()->json($this->transformAgendaForFrontend($agenda), 201);
    }

    public function show(Agenda $agenda): JsonResponse
    {
        $agenda->load(['orden']);

        return response()->json($this->transformAgendaForFrontend($agenda));
    }

    public function update(Request $request, Agenda $agenda): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'orden_id' => 'sometimes|nullable|exists:ordenes,id',
            'id_orden_firebase' => 'sometimes|nullable|string|max:255',
            'start' => 'sometimes|nullable|string|max:255',
            'start_raw' => 'sometimes|nullable|string|max:255',
            'fecha' => 'sometimes|nullable|string|max:255',
            'all_day' => 'sometimes|nullable|boolean',
            'text_color' => 'sometimes|nullable|string|max:50',
            'title' => 'sometimes|nullable|string|max:255',
            'equipo_data' => 'sometimes|nullable|array',
            'block' => 'sometimes|nullable|boolean',
            'estatus' => 'sometimes|nullable|boolean',
        ]);

        if (array_key_exists('start', $validated) && !empty($validated['start'])) {
            $startInput = trim((string) $validated['start']);
            if (empty($validated['start_raw'])) {
                $validated['start_raw'] = $startInput;
            }

            $normalizedStart = $this->normalizeStartForDatabase($startInput);
            if ($normalizedStart === null) {
                return response()->json([
                    'message' => 'El campo start no tiene un formato de fecha/hora valido.',
                ], 422);
            }

            $validated['start'] = $normalizedStart;
        }

        if (array_key_exists('id_orden_firebase', $validated) && empty($validated['orden_id']) && !empty($validated['id_orden_firebase'])) {
            $validated['orden_id'] = Orden::where('firebase_id', $validated['id_orden_firebase'])->value('id');
        }

        $agenda->update($validated);
        $agenda->load(['orden']);

        return response()->json($this->transformAgendaForFrontend($agenda));
    }

    public function destroy(Agenda $agenda): JsonResponse
    {
        $agenda->delete();
        return response()->json(['status' => 'deleted']);
    }

    private function resolveAgendaDate(Agenda $agenda): ?Carbon
    {
        if ($agenda->start instanceof Carbon) {
            return $agenda->start;
        }

        if (!empty($agenda->start)) {
            try {
                return Carbon::parse((string) $agenda->start);
            } catch (\Throwable) {
                // Continua con fallback.
            }
        }

        if (!empty($agenda->start_raw)) {
            try {
                return Carbon::parse((string) $agenda->start_raw);
            } catch (\Throwable) {
                // Continua con fallback.
            }
        }

        if (!empty($agenda->fecha)) {
            try {
                return Carbon::parse((string) $agenda->fecha);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function resolveAlertColor(int $daysLeft): ?string
    {
        if ($daysLeft <= 1) {
            return 'red';
        }

        if ($daysLeft <= 3) {
            return 'yellow';
        }

        if ($daysLeft <= 5) {
            return 'green';
        }

        return null;
    }

    private function resolveAlertLevel(int $daysLeft): string
    {
        if ($daysLeft <= 1) {
            return 'critica';
        }

        if ($daysLeft <= 3) {
            return 'media';
        }

        return 'preventiva';
    }

    private function transformAgendaForFrontend(Agenda $agenda): array
    {
        $payload = $agenda->toArray();
        $payload['allDay'] = (bool) ($payload['all_day'] ?? false);
        $payload['textColor'] = $payload['text_color'] ?? null;
        $payload['startStr'] = (string) ($payload['start'] ?? ($payload['start_raw'] ?? ''));

        return $payload;
    }

    private function normalizeStartForDatabase(string $startInput): ?string
    {
        try {
            return Carbon::parse($startInput)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
