<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AgentLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private const RESERVED_PAYLOAD_KEYS = [
        'id',
        'data',
        'firebase_id',
        'name',
        'nombre',
        'nickname',
        'email',
        'password',
        'phone',
        'telefono',
        'mobile',
        'celular',
        'position',
        'puesto',
        'is_active',
        'activo',
        'admin',
        'fecha',
        'module_permissions',
        'folio_settings',
    ];

    /**
     * Lista todos los asesores (rol asesor).
     * Solo admin.
     */
    public function index()
    {
        $asesores = User::role('asesor')
            ->withCount(['assignedConversations as active_conversations' => function ($q) {
                $q->where('status', 'active');
            }])
            ->get()
            ->map(fn ($u) => array_merge($u->toLegacyPayload(), [
                'roles' => $u->getRoleNames(),
                'active_conversations' => $u->active_conversations,
            ]));

        return response()->json($asesores);
    }

    /**
     * Devuelve el estado del limite de asesores configurado.
     * Solo admin.
     */
    public function limits(AgentLimitService $agentLimit)
    {
        return response()->json($agentLimit->snapshot());
    }

    /**
     * Crea un nuevo asesor.
     * Solo admin.
     */
    public function store(Request $request, AgentLimitService $agentLimit)
    {
        $limitSnapshot = $agentLimit->snapshot();

        if (!$limitSnapshot['can_create']) {
            return response()->json([
                'error' => 'Se alcanzó el máximo de asesores permitidos.',
                'limits' => $limitSnapshot,
            ], 422);
        }

        $payload = $this->payload($request);
        $validated = $this->validateUserPayload($payload);
        $user = User::create($this->buildUserAttributes($payload, $validated));

        $this->syncUserRole($user, $payload);

        return response()->json([
            'user' => array_merge($user->toLegacyPayload(), [
                'roles' => $user->getRoleNames(),
            ]),
            'limits' => $agentLimit->snapshot(),
        ], 201);
    }

    /**
     * Actualiza nombre / email / contraseña de un asesor.
     * Solo admin.
     */
    public function update(Request $request, User $user)
    {
        $payload = $this->payload($request);
        $validated = $this->validateUserPayload($payload, $user);
        $user->update($this->buildUserAttributes($payload, $validated, $user));
        $this->syncUserRole($user, $payload);

        $user->refresh();

        return response()->json([
            'status' => 'updated',
            'user' => array_merge($user->toLegacyPayload(), [
                'roles' => $user->getRoleNames(),
            ]),
        ]);
    }

    /**
     * Actualiza solo los permisos de módulos de un usuario.
     * Acepta el payload plano de Firebase (cliente, cotizacion, etc.)
     * o un objeto module_permissions. Es un merge: solo cambia lo que se envía.
     * Solo admin.
     */
    public function updatePermisos(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $payload = $this->payload($request);

        // Recolectar permisos: estructura nueva o claves planas
        $incoming = array_key_exists('module_permissions', $payload) && is_array($payload['module_permissions'])
            ? $payload['module_permissions']
            : [];

        foreach ($payload as $key => $value) {
            if (in_array($key, self::RESERVED_PAYLOAD_KEYS, true)) {
                continue;
            }
            if (is_bool($value)) {
                $incoming[$key] = $value;
            }
        }

        if (empty($incoming)) {
            return response()->json(['error' => 'No se enviaron permisos válidos.'], 422);
        }

        // Upsert en tabla modulo_permisos (un registro por módulo)
        foreach ($incoming as $modulo => $habilitado) {
            \App\Models\ModuloPermiso::updateOrCreate(
                ['user_id' => $user->id, 'modulo' => $modulo],
                ['habilitado' => (bool) $habilitado]
            );
        }

        $user->refresh();

        return response()->json([
            'status'             => 'updated',
            'module_permissions' => $user->getModulePermissions(),
        ]);
    }

    /**
     * Incrementa los folios de un usuario (contadores acumulativos).
     *
     * El valor enviado se SUMA al existente, no lo reemplaza.
     * Así cada módulo lleva su propio conteo de folios creados.
     *
     * Acepta payload plano (Cfolio, Dfolio, lastfolio, *folio...)
     * o un objeto folio_settings.
     *
     * Excepción: si se envía "set": true en el payload, los valores
     * se asignan directamente (útil para corrección manual desde admin).
     *
     * Solo admin.
     */
    public function updateFolios(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $payload = $this->payload($request);
        $forceSet = !empty($payload['set']);

        // Recolectar folios: estructura nueva o claves planas
        $incoming = array_key_exists('folio_settings', $payload) && is_array($payload['folio_settings'])
            ? array_map('intval', $payload['folio_settings'])
            : [];

        foreach ($payload as $key => $value) {
            if (in_array($key, self::RESERVED_PAYLOAD_KEYS, true)) {
                continue;
            }
            if ($this->isFolioKey($key) && is_numeric($value)) {
                $incoming[$key] = (int) $value;
            }
        }

        if (empty($incoming)) {
            return response()->json(['error' => 'No se enviaron folios válidos.'], 422);
        }

        $current = [
            'Cfolio'    => (int) ($user->Cfolio ?? 0),
            'Dfolio'    => (int) ($user->Dfolio ?? 0),
            'lastfolio' => (int) ($user->lastfolio ?? 0),
        ];

        if ($forceSet) {
            $result = array_merge($current, $incoming);
        } else {
            $result = $current;
            foreach ($incoming as $key => $delta) {
                $result[$key] = (int) ($result[$key] ?? 0) + $delta;
            }
        }

        // Actualizar columnas directas en users
        $user->update(array_filter($result, fn ($k) => in_array($k, ['Cfolio','Dfolio','lastfolio']), ARRAY_FILTER_USE_KEY));
        $user->refresh();

        return response()->json([
            'status'         => 'updated',
            'folio_settings' => $user->getFolioSettings(),
        ]);
    }

    /**
     * Elimina un asesor.
     * Solo admin.
     */
    public function destroy(User $user)
    {
        // No permitir que el admin se elimine a sí mismo
        if ($user->hasRole('admin')) {
            return response()->json(['error' => 'No puedes eliminar al administrador.'], 403);
        }

        $user->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Asigna una conversación a un asesor.
     * Solo admin.
     */
    public function assignConversation(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'user_id'         => 'required|exists:users,id',
        ]);

        $asesor = User::findOrFail($validated['user_id']);

        if (!$asesor->hasRole('asesor')) {
            return response()->json(['error' => 'El usuario no es un asesor.'], 422);
        }

        \App\Models\Conversation::find($validated['conversation_id'])
            ->update(['assigned_to' => $validated['user_id']]);

        return response()->json(['status' => 'assigned']);
    }

    /**
     * Quita la asignación de una conversación.
     * Solo admin.
     */
    public function unassignConversation(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        \App\Models\Conversation::find($validated['conversation_id'])
            ->update(['assigned_to' => null]);

        return response()->json(['status' => 'unassigned']);
    }

    private function payload(Request $request): array
    {
        $payload = $request->input('data');

        if (!is_array($payload)) {
            $payload = $request->all();
        }

        if (array_key_exists('id', $request->all()) && !array_key_exists('id', $payload)) {
            $payload['id'] = $request->input('id');
        }

        return $payload;
    }

    private function validateUserPayload(array $payload, ?User $user = null): array
    {
        $firebaseUnique = 'unique:users,firebase_id';
        $nicknameUnique = 'unique:users,nickname';
        $emailUnique = 'unique:users,email';

        if ($user) {
            $firebaseUnique .= ',' . $user->id;
            $nicknameUnique .= ',' . $user->id;
            $emailUnique .= ',' . $user->id;
        }

        return Validator::make($payload, [
            'id' => ['sometimes', 'nullable', 'string', 'max:255', $firebaseUnique],
            'firebase_id' => ['sometimes', 'nullable', 'string', 'max:255', $firebaseUnique],
            'name' => [$user ? 'sometimes' : 'required_without:nombre', 'nullable', 'string', 'max:255'],
            'nombre' => [$user ? 'sometimes' : 'required_without:name', 'nullable', 'string', 'max:255'],
            'nickname' => [$user ? 'sometimes' : 'required', 'string', 'max:255', $nicknameUnique],
            'email' => [$user ? 'sometimes' : 'required', 'email', 'max:255', $emailUnique],
            'password' => [$user ? 'sometimes' : 'required', 'string', 'min:8'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:255'],
            'celular' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'puesto' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
            'admin' => ['sometimes', 'boolean'],
            'module_permissions' => ['sometimes', 'array'],
            'folio_settings' => ['sometimes', 'array'],
        ])->validate();
    }

    private function buildUserAttributes(array $payload, array $validated, ?User $user = null): array
    {
        $attributes = [];
        $firebaseId = $validated['firebase_id'] ?? $validated['id'] ?? null;

        if (array_key_exists('firebase_id', $validated) || array_key_exists('id', $validated) || !$user) {
            $attributes['firebase_id'] = $firebaseId;
        }

        if (array_key_exists('name', $validated) || array_key_exists('nombre', $validated) || !$user) {
            $attributes['name'] = $validated['nombre'] ?? $validated['name'] ?? $user?->name;
        }

        if (array_key_exists('nickname', $validated) || !$user) {
            $attributes['nickname'] = $validated['nickname'] ?? $user?->nickname;
        }

        if (array_key_exists('email', $validated) || !$user) {
            $attributes['email'] = $validated['email'] ?? $user?->email;
        }

        if (array_key_exists('password', $validated)) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        if (array_key_exists('phone', $validated) || array_key_exists('telefono', $validated) || !$user) {
            $attributes['phone'] = $validated['telefono'] ?? $validated['phone'] ?? $user?->phone;
        }

        if (array_key_exists('mobile', $validated) || array_key_exists('celular', $validated) || !$user) {
            $attributes['mobile'] = $validated['celular'] ?? $validated['mobile'] ?? $user?->mobile;
        }

        if (array_key_exists('position', $validated) || array_key_exists('puesto', $validated) || !$user) {
            $attributes['position'] = $validated['puesto'] ?? $validated['position'] ?? $user?->position;
        }

        if (array_key_exists('is_active', $validated) || array_key_exists('activo', $validated) || !$user) {
            $attributes['is_active'] = (bool) ($validated['activo'] ?? $validated['is_active'] ?? $user?->is_active ?? true);
        }

        [$modulePermissions, $folioSettings] = $this->extractDynamicSettings($payload, $user);

        if ($modulePermissions !== null) {
            $attributes['module_permissions'] = $modulePermissions;
        }

        if ($folioSettings !== null) {
            $attributes['folio_settings'] = $folioSettings;
        }

        return $attributes;
    }

    private function extractDynamicSettings(array $payload, ?User $user = null): array
    {
        $currentModules = $user?->module_permissions ?? [];
        $currentFolios = $user?->folio_settings ?? [];

        $modulePermissions = array_key_exists('module_permissions', $payload) && is_array($payload['module_permissions'])
            ? $payload['module_permissions']
            : $currentModules;

        $folioSettings = array_key_exists('folio_settings', $payload) && is_array($payload['folio_settings'])
            ? $payload['folio_settings']
            : $currentFolios;

        $hasDynamicModules = array_key_exists('module_permissions', $payload);
        $hasDynamicFolios = array_key_exists('folio_settings', $payload);

        foreach ($payload as $key => $value) {
            if (in_array($key, self::RESERVED_PAYLOAD_KEYS, true)) {
                continue;
            }

            if (is_bool($value)) {
                $modulePermissions[$key] = $value;
                $hasDynamicModules = true;
                continue;
            }

            if ($this->isFolioKey($key) && is_numeric($value)) {
                $folioSettings[$key] = (int) $value;
                $hasDynamicFolios = true;
            }
        }

        return [
            $hasDynamicModules ? $modulePermissions : null,
            $hasDynamicFolios ? $folioSettings : null,
        ];
    }

    private function isFolioKey(string $key): bool
    {
        return str_ends_with(strtolower($key), 'folio') || $key === 'lastfolio';
    }

    private function syncUserRole(User $user, array $payload): void
    {
        if (!array_key_exists('admin', $payload)) {
            if (!$user->hasAnyRole(['admin', 'asesor'])) {
                $user->assignRole('asesor');
            }

            return;
        }

        $user->syncRoles([(bool) $payload['admin'] ? 'admin' : 'asesor']);
    }
}
