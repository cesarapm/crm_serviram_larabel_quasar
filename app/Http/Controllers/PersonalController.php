<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PersonalController extends Controller
{
    /**
     * Lista personal (rol asesor).
     */
    public function index(): JsonResponse
    {
        $personal = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['asesor', 'admin']);
            })
            ->get()
            ->map(fn ($u) => array_merge($u->toLegacyPayload(), [
                'almacen' => (bool) data_get($u->getModulePermissions(), 'almacen', false),
                'roles' => $u->getRoleNames(),
            ]));

        return response()->json($personal);
    }

    /**
     * Crea personal con módulos en false por defecto.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nickname' => 'required|string|max:255|unique:users,nickname',
            'password' => 'required|string|min:3',
            'email' => 'required|email|max:255|unique:users,email',
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'celular' => 'nullable|string|max:50',
            'puesto' => 'nullable|string|max:255',
        ]);

        do {
            $firebaseId = $this->generateLetterOnlyFirebaseId(20);
        } while (User::where('firebase_id', $firebaseId)->exists());

        $user = User::create([
            'firebase_id' => $firebaseId,
            'name' => $data['nombre'],
            'nickname' => $data['nickname'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['telefono'] ?? null,
            'mobile' => $data['celular'] ?? null,
            'position' => $data['puesto'] ?? null,
            'activo' => true,
            'Cfolio' => 0,
            'Dfolio' => 0,
            'lastfolio' => 0,
        ]);

        $user->assignRole('asesor');

        return response()->json([
            'user' => array_merge($user->toLegacyPayload(), [
                'almacen' => (bool) data_get($user->getModulePermissions(), 'almacen', false),
                'roles' => $user->getRoleNames(),
            ]),
        ], 201);
    }

    private function generateLetterOnlyFirebaseId(int $length = 20): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($characters) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $maxIndex)];
        }

        return $result;
    }

    /**
     * Actualiza datos de personal.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (!$user->hasAnyRole(['asesor', 'admin'])) {
            return response()->json(['error' => 'Solo se puede actualizar personal con rol asesor o admin.'], 422);
        }

        $validated = $request->validate([
            'nickname' => 'sometimes|string|max:255|unique:users,nickname,' . $user->id,
            'password' => 'sometimes|string|min:3',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|nullable|string|max:50',
            'celular' => 'sometimes|nullable|string|max:50',
            'puesto' => 'sometimes|nullable|string|max:255',
            'activo' => 'sometimes|boolean',
            'admin' => 'sometimes|boolean',
        ]);

        $attributes = [];

        if (array_key_exists('nickname', $validated)) {
            $attributes['nickname'] = $validated['nickname'];
        }
        if (array_key_exists('email', $validated)) {
            $attributes['email'] = $validated['email'];
        }
        if (array_key_exists('nombre', $validated)) {
            $attributes['name'] = $validated['nombre'];
        }
        if (array_key_exists('telefono', $validated)) {
            $attributes['phone'] = $validated['telefono'];
        }
        if (array_key_exists('celular', $validated)) {
            $attributes['mobile'] = $validated['celular'];
        }
        if (array_key_exists('puesto', $validated)) {
            $attributes['position'] = $validated['puesto'];
        }
        if (array_key_exists('activo', $validated)) {
            $attributes['activo'] = (bool) $validated['activo'];
        }
        if (array_key_exists('password', $validated)) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        if (!empty($attributes)) {
            $user->update($attributes);
        }

        if (array_key_exists('admin', $validated)) {
            $makeAdmin = (bool) $validated['admin'];

            // Evitar que el admin actual se quite su propio rol por accidente.
            if (!$makeAdmin && $request->user()?->id === $user->id) {
                return response()->json(['error' => 'No puedes quitarte a ti mismo el rol admin.'], 422);
            }

            $user->syncRoles([$makeAdmin ? 'admin' : 'asesor']);
        }

        $user->refresh();

        return response()->json([
            'status' => 'updated',
            'user' => array_merge($user->toLegacyPayload(), [
                'almacen' => (bool) data_get($user->getModulePermissions(), 'almacen', false),
                'roles' => $user->getRoleNames(),
            ]),
        ]);
    }

    /**
     * Elimina personal.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->hasRole('admin')) {
            return response()->json(['error' => 'No puedes eliminar al administrador.'], 403);
        }

        if (!$user->hasRole('asesor')) {
            return response()->json(['error' => 'Solo se puede eliminar personal con rol asesor.'], 422);
        }

        $user->delete();

        return response()->json(['status' => 'deleted']);
    }
}