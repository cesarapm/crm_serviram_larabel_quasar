<?php

use Illuminate\Support\Facades\Route;

// API documentation route
Route::get('/', function () {
    return response()->json([
        'message' => 'CRM Serviram API',
        'version' => '1.0',
        'endpoints' => [
            'GET /api/reportes/servicios-diarios' => 'Reporte diario de servicios por usuario',
            'GET /api/reportes/servicios-semanales' => 'Reporte semanal de servicios por usuario',
            'GET /api/reportes/servicios-mensuales' => 'Reporte mensual de servicios por usuario',
            'POST /api/buzonclave' => 'Crear servicio en buzón (requiere X-API-KEY)',
        ]
    ]);
});

// Removed problematic catch-all route that was looking for non-existent index.html
