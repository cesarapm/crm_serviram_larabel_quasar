<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Lista todos los productos.
     */
    public function index(): JsonResponse
    {
        $products = Product::all();
        return response()->json($products);
    }

    /**
     * Crea un nuevo producto.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'nullable|string|unique:products,firebase_id',
            'nombre' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:255',
            'linea' => 'nullable|string|max:255',
            'negocio' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'mantenimiento' => 'nullable|string|max:255',
            'condicion' => 'nullable|integer|min:1|max:3',
            'ultima' => 'nullable|date_format:Y-m-d H:i',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Muestra un producto específico.
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Actualiza un producto.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'firebase_id' => 'sometimes|string|unique:products,firebase_id,' . $product->id,
            'nombre' => 'sometimes|string|max:255',
            'marca' => 'sometimes|nullable|string|max:255',
            'modelo' => 'sometimes|nullable|string|max:255',
            'serie' => 'sometimes|nullable|string|max:255',
            'linea' => 'sometimes|nullable|string|max:255',
            'negocio' => 'sometimes|nullable|string|max:255',
            'ubicacion' => 'sometimes|nullable|string|max:255',
            'mantenimiento' => 'sometimes|nullable|string|max:255',
            'condicion' => 'sometimes|nullable|integer|min:1|max:3',
            'ultima' => 'sometimes|nullable|date_format:Y-m-d H:i',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Elimina un producto.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(['status' => 'deleted']);
    }
}
