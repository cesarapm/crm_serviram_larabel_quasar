<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login'    => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'email'    => 'nullable|string|max:255',
            'password' => 'required|string',
        ]);

        $identifier = $validated['login'] ?? $validated['nickname'] ?? $validated['email'] ?? null;

        if (!$identifier) {
            throw ValidationException::withMessages([
                'login' => ['Debes indicar nickname o email.'],
            ]);
        }

        // FacadesLog::info('Login attempt', ['login' => $identifier]);

        // Buscar al usuario por nickname o email

        $user = User::query()
            ->where('nickname', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales no son correctas.'],
            ]);
        }

        if (!$user->activo) {
            throw ValidationException::withMessages([
                'login' => ['Tu usuario está inactivo.'],
            ]);
        }

        $token = $user->createToken('chatbot-front')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => array_merge($user->toLegacyPayload(), [
                'almacen' => $user->hasModuleAccess('almacen'),
                'roles' => $user->getRoleNames(),
            ]),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
