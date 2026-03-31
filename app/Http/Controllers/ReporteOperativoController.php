<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Cotizacion;
use App\Models\Agenda;
use App\Models\Orden;
use App\Models\Cliente;
use Carbon\Carbon;

class ReporteOperativoController extends Controller
{
    // 1. Reportes Diarios
    public function serviciosDiarios(Request $request): JsonResponse
    {
        $fecha = $request->input('fecha', Carbon::today()->toDateString());
        $programados = Agenda::whereDate('start', $fecha)->count();
        $realizados = Servicio::whereDate('created_at', $fecha)->count();
        return response()->json([
            'fecha' => $fecha,
            'programados' => $programados,
            'realizados' => $realizados,
        ]);
    }

    public function facturacionDiaria(Request $request): JsonResponse
    {
        $fecha = $request->input('fecha', Carbon::today()->toDateString());
        $total = Cotizacion::whereDate('created_at', $fecha)->where('estatus', 'cerrada')->sum('total');
        return response()->json([
            'fecha' => $fecha,
            'total' => $total,
        ]);
    }

    public function serviciosPorTecnico(Request $request): JsonResponse
    {
        $fecha = $request->input('fecha', Carbon::today()->toDateString());
        $servicios = Servicio::whereDate('created_at', $fecha)
            ->with('usuario')
            ->get()
            ->groupBy('usuario_id')
            ->map(function ($items, $usuario_id) {
                $usuario = User::find($usuario_id);
                return [
                    'tecnico' => $usuario ? $usuario->name : 'Sin asignar',
                    'total' => $items->count(),
                ];
            })
            ->values();
        return response()->json($servicios);
    }

    public function tiemposMuertos(Request $request): JsonResponse
    {
        $fecha = $request->input('fecha', Carbon::today()->toDateString());
        $tecnicos = User::whereHas('roles', function($q){ $q->where('name', 'tecnico'); })->get();
        $result = [];
        foreach ($tecnicos as $tecnico) {
            $agendas = Agenda::where('usuario_id', $tecnico->id)
                ->whereDate('start', $fecha)
                ->orderBy('start')
                ->get();
            $huecos = [];
            $prevEnd = null;
            foreach ($agendas as $agenda) {
                if ($prevEnd && $agenda->start > $prevEnd) {
                    $huecos[] = [
                        'inicio' => $prevEnd,
                        'fin' => $agenda->start,
                    ];
                }
                $prevEnd = $agenda->end;
            }
            $result[] = [
                'tecnico' => $tecnico->name,
                'huecos' => $huecos,
            ];
        }
        return response()->json($result);
    }

    // 2. Reportes Semanales
    public function facturacionSemanal(Request $request): JsonResponse
    {
        $semana = $request->input('semana', Carbon::now()->weekOfYear);
        $anio = $request->input('anio', Carbon::now()->year);
        $total = Cotizacion::where('estatus', 'cerrada')
            ->whereYear('created_at', $anio)
            ->whereRaw('WEEK(created_at, 1) = ?', [$semana])
            ->sum('total');
        return response()->json([
            'semana' => $semana,
            'anio' => $anio,
            'total' => $total,
        ]);
    }

    public function nuevosClientes(Request $request): JsonResponse
    {
        $semana = $request->input('semana', Carbon::now()->weekOfYear);
        $anio = $request->input('anio', Carbon::now()->year);
        $clientes = Cliente::whereYear('created_at', $anio)
            ->whereRaw('WEEK(created_at, 1) = ?', [$semana])
            ->get();
        return response()->json($clientes);
    }

    public function cotizacionesSemanales(Request $request): JsonResponse
    {
        $semana = $request->input('semana', Carbon::now()->weekOfYear);
        $anio = $request->input('anio', Carbon::now()->year);
        $enviadas = Cotizacion::whereYear('created_at', $anio)
            ->whereRaw('WEEK(created_at, 1) = ?', [$semana])
            ->where('estatus', 'enviada')
            ->count();
        $cerradas = Cotizacion::whereYear('created_at', $anio)
            ->whereRaw('WEEK(created_at, 1) = ?', [$semana])
            ->where('estatus', 'cerrada')
            ->count();
        return response()->json([
            'semana' => $semana,
            'anio' => $anio,
            'enviadas' => $enviadas,
            'cerradas' => $cerradas,
        ]);
    }

    public function productividadTecnico(Request $request): JsonResponse
    {
        $semana = $request->input('semana', Carbon::now()->weekOfYear);
        $anio = $request->input('anio', Carbon::now()->year);
        $tecnicos = User::whereHas('roles', function($q){ $q->where('name', 'tecnico'); })->get();
        $result = [];
        foreach ($tecnicos as $tecnico) {
            $servicios = Servicio::where('usuario_id', $tecnico->id)
                ->whereYear('created_at', $anio)
                ->whereRaw('WEEK(created_at, 1) = ?', [$semana])
                ->count();
            $facturacion = Cotizacion::where('usuario_id', $tecnico->id)
                ->where('estatus', 'cerrada')
                ->whereYear('created_at', $anio)
                ->whereRaw('WEEK(created_at, 1) = ?', [$semana])
                ->sum('total');
            $result[] = [
                'tecnico' => $tecnico->name,
                'servicios' => $servicios,
                'facturacion' => $facturacion,
            ];
        }
        return response()->json($result);
    }
}
