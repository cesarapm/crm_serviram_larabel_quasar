<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\BusinessContextController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\EncuestaServicioController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\GmServicioController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TipoEquipoController;
use App\Http\Controllers\UserController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Auth (sin middleware) ────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json(array_merge($user->toLegacyPayload(), [
            'almacen' => $user->hasModuleAccess('almacen'),
            'roles' => $user->getRoleNames(),
        ]));
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ─── Contexto del negocio (público para n8n) ─────────────────────────────────
Route::get('/business-context', [BusinessContextController::class, 'index']);

// ─── Rutas para admin y asesores ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Conversaciones (admin ve todas, asesor solo las suyas)
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::patch('/conversations/{conversation}/toggle-human', [ConversationController::class, 'toggleHuman']);
    Route::post('/conversations/{conversation}/send', [ConversationController::class, 'sendHuman']);
    Route::patch('/contacts/{contact}/name', [ContactController::class, 'updateName'])
        ->middleware('role:admin|asesor');

    // Estado de cuota mensual de mensajes
});

// ─── Rutas exclusivas de Admin ───────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Gestión de asesores
    Route::get('/asesores', [UserController::class, 'index']);
    Route::get('/asesores/limits', [UserController::class, 'limits']);
    Route::post('/asesores', [UserController::class, 'store']);
    Route::put('/asesores/{user}', [UserController::class, 'update']);
    Route::patch('/asesores/{user}/permisos', [UserController::class, 'updatePermisos']);
    Route::patch('/asesores/{user}/folios', [UserController::class, 'updateFolios']);
    Route::delete('/asesores/{user}', [UserController::class, 'destroy']);
    // Gestión de personal (formulario simple, permisos en false por defecto)
    Route::get('/personal', [PersonalController::class, 'index']);
    Route::post('/personal', [PersonalController::class, 'store']);
    Route::put('/personal/{user}', [PersonalController::class, 'update']);
    Route::delete('/personal/{user}', [PersonalController::class, 'destroy']);
    // Gestión de productos
    Route::get('/productos', [ProductController::class, 'index']);
    Route::post('/productos', [ProductController::class, 'store']);
    Route::get('/productos/{product}', [ProductController::class, 'show']);
    Route::put('/productos/{product}', [ProductController::class, 'update']);
    Route::delete('/productos/{product}', [ProductController::class, 'destroy']);
    // Gestión de clientes
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show']);
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update']);
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy']);
    // Gestión de tipos de equipo
    Route::get('/tipoequipos', [TipoEquipoController::class, 'index']);
    Route::post('/tipoequipos', [TipoEquipoController::class, 'store']);
    Route::get('/tipoequipos/{tipoEquipo}', [TipoEquipoController::class, 'show']);
    Route::put('/tipoequipos/{tipoEquipo}', [TipoEquipoController::class, 'update']);
    Route::delete('/tipoequipos/{tipoEquipo}', [TipoEquipoController::class, 'destroy']);
    // Gestión de servicios
    Route::get('/servicios', [ServicioController::class, 'index']);
    Route::post('/servicios', [ServicioController::class, 'store']);
    Route::get('/servicios/{servicio}', [ServicioController::class, 'show']);
    Route::put('/servicios/{servicio}', [ServicioController::class, 'update']);
    Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy']);
    Route::get('/servicios-list', [ServicioController::class, 'list']);

    // Gestion de servicios GM
    Route::get('/gm-servicios', [GmServicioController::class, 'index']);
    Route::post('/gm-servicios', [GmServicioController::class, 'store']);
    Route::get('/gm-servicios/{gmServicio}', [GmServicioController::class, 'show']);
    Route::put('/gm-servicios/{gmServicio}', [GmServicioController::class, 'update']);
    Route::delete('/gm-servicios/{gmServicio}', [GmServicioController::class, 'destroy']);
    Route::get('/gm-servicios-list', [GmServicioController::class, 'list']);

    // Gestion de encuestas de servicios
    Route::get('/encuesta-servicios', [EncuestaServicioController::class, 'index']);
    Route::post('/encuesta-servicios', [EncuestaServicioController::class, 'store']);
    Route::get('/encuesta-servicios/{encuestasServicio}', [EncuestaServicioController::class, 'show']);
    Route::put('/encuesta-servicios/{encuestasServicio}', [EncuestaServicioController::class, 'update']);
    Route::delete('/encuesta-servicios/{encuestasServicio}', [EncuestaServicioController::class, 'destroy']);
    Route::get('/encuesta-servicios-list', [EncuestaServicioController::class, 'list']);

    // Gestión de cotizaciones
    Route::get('/cotizaciones', [CotizacionController::class, 'index']);
    Route::post('/cotizaciones', [CotizacionController::class, 'store']);
    Route::get('/cotizaciones/{cotizacion}', [CotizacionController::class, 'show']);
    Route::put('/cotizaciones/{cotizacion}', [CotizacionController::class, 'update']);
    Route::delete('/cotizaciones/{cotizacion}', [CotizacionController::class, 'destroy']);
    Route::get('/cotizaciones-list', [CotizacionController::class, 'list']);

    // Gestión de ordenes
    Route::get('/ordenes', [OrdenController::class, 'index']);
    Route::post('/ordenes', [OrdenController::class, 'store']);
    Route::get('/ordenes/{orden}', [OrdenController::class, 'show']);
    Route::put('/ordenes/{orden}', [OrdenController::class, 'update']);
    Route::delete('/ordenes/{orden}', [OrdenController::class, 'destroy']);
    Route::get('/ordenes-list', [OrdenController::class, 'list']);

    // Gestión de agenda
    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::post('/agenda', [AgendaController::class, 'store']);
    Route::get('/agenda/{agenda}', [AgendaController::class, 'show']);
    Route::put('/agenda/{agenda}', [AgendaController::class, 'update']);
    Route::delete('/agenda/{agenda}', [AgendaController::class, 'destroy']);
    Route::get('/agenda-list', [AgendaController::class, 'list']);
    Route::get('/agenda/orden/{orden}', [AgendaController::class, 'byOrden']);
    Route::get('/agenda-alertas', [AgendaController::class, 'alertas']);

    // Gestión de almacén
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::put('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
    Route::get('/items-buscar', [ItemController::class, 'buscar']);
    Route::get('/items-estadisticas', [ItemController::class, 'estadisticas']);
    Route::get('/items-racks', [ItemController::class, 'racks']);
    Route::patch('/items/{item}/ajustar-stock', [ItemController::class, 'ajustarStock']);
    Route::get('/items/{item}/movimientos', [ItemController::class, 'historialMovimientos']);

    // Gestión de racks
    Route::get('/racks', [RackController::class, 'index']);
    Route::post('/racks', [RackController::class, 'store']);
    Route::get('/racks/{rack}', [RackController::class, 'show']);
    Route::put('/racks/{rack}', [RackController::class, 'update']);
    Route::delete('/racks/{rack}', [RackController::class, 'destroy']);
    Route::get('/racks-estadisticas', [RackController::class, 'estadisticas']);
    Route::get('/racks/{rack}/items', [RackController::class, 'itemsEnRack']);

        // ─── Reportes y KPIs Operativos ──────────────────────────────────────────────
        Route::prefix('reportes')->group(function () {
            Route::get('/servicios-diarios', [\App\Http\Controllers\ReporteOperativoController::class, 'serviciosDiarios']);
            Route::get('/facturacion-diaria', [\App\Http\Controllers\ReporteOperativoController::class, 'facturacionDiaria']);
            Route::get('/servicios-por-tecnico', [\App\Http\Controllers\ReporteOperativoController::class, 'serviciosPorTecnico']);
            Route::get('/tiempos-muertos', [\App\Http\Controllers\ReporteOperativoController::class, 'tiemposMuertos']);
            Route::get('/facturacion-semanal', [\App\Http\Controllers\ReporteOperativoController::class, 'facturacionSemanal']);
            Route::get('/nuevos-clientes', [\App\Http\Controllers\ReporteOperativoController::class, 'nuevosClientes']);
            Route::get('/cotizaciones-semanales', [\App\Http\Controllers\ReporteOperativoController::class, 'cotizacionesSemanales']);
            Route::get('/productividad-tecnico', [\App\Http\Controllers\ReporteOperativoController::class, 'productividadTecnico']);
        });
        Route::prefix('alertas')->group(function () {
            // Endpoints de alertas automáticas (pendientes de implementar lógica)
            // Route::get('/mantenimiento', [\App\Http\Controllers\ReporteOperativoController::class, 'alertasMantenimiento']);
            // Route::get('/clientes-inactivos', [\App\Http\Controllers\ReporteOperativoController::class, 'alertasClientesInactivos']);
            // Route::get('/fallas-repetitivas', [\App\Http\Controllers\ReporteOperativoController::class, 'alertasFallasRepetitivas']);
            // Route::get('/cotizaciones-pendientes', [\App\Http\Controllers\ReporteOperativoController::class, 'alertasCotizacionesPendientes']);
        });


    // Asignar / desasignar conversación
    Route::post('/assign', [UserController::class, 'assignConversation']);
    Route::post('/unassign', [UserController::class, 'unassignConversation']);
    // CRUD contexto del negocio
    Route::post('/business-context', [BusinessContextController::class, 'store']);
    Route::put('/business-context/{businessContext}', [BusinessContextController::class, 'update']);
    Route::delete('/business-context/{businessContext}', [BusinessContextController::class, 'destroy']);
});
