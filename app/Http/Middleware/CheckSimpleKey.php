<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSimpleKey
{
    /**
     * Maneja una petición entrante.
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-KEY');
        $expectedKey = config('services.buzon_simple_key');

        if (!$key || $key !== $expectedKey) {
            return response()->json(['message' => 'Clave inválida'], 401);
        }

        return $next($request);
    }
}
