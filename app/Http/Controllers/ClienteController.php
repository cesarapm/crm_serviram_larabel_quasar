<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(): JsonResponse
    {
        $clientes = Cliente::query()
            ->orderBy('compania')
            ->orderBy('contacto')
            ->get();

        return response()->json($clientes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|max:255',
            'compania' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'responsable' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'ciudad' => 'nullable|string|max:255',
            'direccion' => 'nullable|string',
        ]);

        $cliente = Cliente::create($validated);

        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente);
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|nullable|string|max:255',
            'compania' => 'sometimes|required|string|max:255',
            'contacto' => 'sometimes|nullable|string|max:255',
            'responsable' => 'sometimes|nullable|string|max:255',
            'telefono' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'ciudad' => 'sometimes|nullable|string|max:255',
            'direccion' => 'sometimes|nullable|string',
        ]);

        $cliente->update($validated);

        return response()->json($cliente);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json(['status' => 'deleted']);
    }
}
