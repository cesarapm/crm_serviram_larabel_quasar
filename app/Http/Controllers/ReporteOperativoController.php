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
use App\Models\GmServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReporteOperativoController extends Controller
{
    /**
     * Reporte diario de servicios por usuario
     * Incluye servicios y gm_servicios del día especificado
     */
    public function serviciosDiarios(Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha', Carbon::today()->toDateString());
            $usuario_id = $request->input('usuario_id');

            // Obtener usuarios con servicios del día
            $usuarios = User::all();
            $resultado = [];

            foreach ($usuarios as $usuario) {
                $servicios_count = Servicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereDate('salida', $fecha)
                    ->count();
                
                $gm_servicios_count = GmServicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereDate('salida', $fecha)
                    ->count();
                
                $total_servicios = $servicios_count + $gm_servicios_count;
                
                // Solo incluir usuarios que tienen servicios
                if ($total_servicios > 0 || $usuario_id == $usuario->id) {
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'servicios_count' => $servicios_count,
                        'gm_servicios_count' => $gm_servicios_count,
                        'total_servicios' => $total_servicios,
                    ];
                }
            }

            // Filtrar por usuario específico si se solicita
            if ($usuario_id) {
                $resultado = array_filter($resultado, function($item) use ($usuario_id) {
                    return $item['id'] == $usuario_id;
                });
            }

            // Ordenar por total descendente
            usort($resultado, function($a, $b) {
                return $b['total_servicios'] <=> $a['total_servicios'];
            });

            return response()->json([
                'fecha' => $fecha,
                'total_usuarios' => count($resultado),
                'total_servicios_dia' => array_sum(array_column($resultado, 'total_servicios')),
                'usuarios' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reporte semanal de servicios por usuario
     * Incluye servicios y gm_servicios de la semana especificada
     */
    public function serviciosSemanales(Request $request): JsonResponse
    {
        try {
            $semana = $request->input('semana', Carbon::now()->weekOfYear);
            $anio = $request->input('anio', Carbon::now()->year);
            $usuario_id = $request->input('usuario_id');

            // Obtener usuarios con servicios de la semana
            $usuarios = User::all();
            $resultado = [];

            foreach ($usuarios as $usuario) {
                $servicios_count = Servicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereYear('salida', $anio)
                    ->whereRaw('WEEK(salida, 1) = ?', [$semana])
                    ->count();
                
                $gm_servicios_count = GmServicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereYear('salida', $anio)
                    ->whereRaw('WEEK(salida, 1) = ?', [$semana])
                    ->count();
                
                $total_servicios = $servicios_count + $gm_servicios_count;
                
                // Solo incluir usuarios que tienen servicios
                if ($total_servicios > 0 || $usuario_id == $usuario->id) {
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'servicios_count' => $servicios_count,
                        'gm_servicios_count' => $gm_servicios_count,
                        'total_servicios' => $total_servicios,
                    ];
                }
            }

            // Filtrar por usuario específico si se solicita
            if ($usuario_id) {
                $resultado = array_filter($resultado, function($item) use ($usuario_id) {
                    return $item['id'] == $usuario_id;
                });
            }

            // Ordenar por total descendente
            usort($resultado, function($a, $b) {
                return $b['total_servicios'] <=> $a['total_servicios'];
            });

            return response()->json([
                'semana' => $semana,
                'anio' => $anio,
                'total_usuarios' => count($resultado),
                'total_servicios_semana' => array_sum(array_column($resultado, 'total_servicios')),
                'usuarios' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reporte mensual de servicios por usuario
     * Incluye servicios y gm_servicios del mes especificado
     */
    public function serviciosMensuales(Request $request): JsonResponse
    {
        try {
            $mes = $request->input('mes', Carbon::now()->month);
            $anio = $request->input('anio', Carbon::now()->year);
            $usuario_id = $request->input('usuario_id');

            // Obtener usuarios con servicios del mes
            $usuarios = User::all();
            $resultado = [];

            foreach ($usuarios as $usuario) {
                $servicios_count = Servicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereYear('salida', $anio)
                    ->whereMonth('salida', $mes)
                    ->count();
                
                $gm_servicios_count = GmServicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereYear('salida', $anio)
                    ->whereMonth('salida', $mes)
                    ->count();
                
                $total_servicios = $servicios_count + $gm_servicios_count;
                
                // Solo incluir usuarios que tienen servicios
                if ($total_servicios > 0 || $usuario_id == $usuario->id) {
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'servicios_count' => $servicios_count,
                        'gm_servicios_count' => $gm_servicios_count,
                        'total_servicios' => $total_servicios,
                    ];
                }
            }

            // Filtrar por usuario específico si se solicita
            if ($usuario_id) {
                $resultado = array_filter($resultado, function($item) use ($usuario_id) {
                    return $item['id'] == $usuario_id;
                });
            }

            // Ordenar por total descendente
            usort($resultado, function($a, $b) {
                return $b['total_servicios'] <=> $a['total_servicios'];
            });

            return response()->json([
                'mes' => $mes,
                'anio' => $anio,
                'total_usuarios' => count($resultado),
                'total_servicios_mes' => array_sum(array_column($resultado, 'total_servicios')),
                'usuarios' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verificar cumplimiento de meta diaria de servicios
     * Meta: 4 servicios por técnico por día
     */
    public function cumplimientoMeta(Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha', Carbon::today()->toDateString());
            $meta_diaria = 4; // Meta de servicios por técnico por día

            // Obtener todos los técnicos (usuarios con servicios)
            $usuarios = User::all();
            $resultado = [];
            $tecnicos_que_cumplen = 0;
            $tecnicos_con_servicios = 0;

            foreach ($usuarios as $usuario) {
                $servicios_count = Servicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereDate('salida', $fecha)
                    ->count();
                
                $gm_servicios_count = GmServicio::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereDate('salida', $fecha)
                    ->count();
                
                $total_servicios = $servicios_count + $gm_servicios_count;
                
                // Solo incluir usuarios que tienen al menos un servicio
                if ($total_servicios > 0) {
                    $cumple_meta = $total_servicios >= $meta_diaria;
                    
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'servicios_count' => $servicios_count,
                        'gm_servicios_count' => $gm_servicios_count,
                        'total_servicios' => $total_servicios,
                        'cumple_meta' => $cumple_meta,
                        'faltante_para_meta' => $cumple_meta ? 0 : ($meta_diaria - $total_servicios)
                    ];
                    
                    $tecnicos_con_servicios++;
                    if ($cumple_meta) {
                        $tecnicos_que_cumplen++;
                    }
                }
            }

            // Ordenar por total de servicios descendente
            usort($resultado, function($a, $b) {
                return $b['total_servicios'] <=> $a['total_servicios'];
            });

            // Calcular porcentaje de cumplimiento
            $porcentaje_cumplimiento = $tecnicos_con_servicios > 0 
                ? round(($tecnicos_que_cumplen / $tecnicos_con_servicios) * 100, 2)
                : 0;

            return response()->json([
                'fecha' => $fecha,
                'meta_diaria' => $meta_diaria,
                'tecnicos_con_servicios' => $tecnicos_con_servicios,
                'tecnicos_que_cumplen' => $tecnicos_que_cumplen,
                'porcentaje_cumplimiento' => $porcentaje_cumplimiento,
                'total_servicios_del_dia' => array_sum(array_column($resultado, 'total_servicios')),
                'detalle_tecnicos' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reporte diario de cotizaciones por usuario
     * Muestra cuántas cotizaciones creó cada usuario en el día especificado
     */
    public function cotizacionesDiarias(Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha', Carbon::today()->toDateString());
            $usuario_id = $request->input('usuario_id');

            // Obtener usuarios con cotizaciones del día
            $usuarios = User::all();
            $resultado = [];

            foreach ($usuarios as $usuario) {
                $cotizaciones_count = Cotizacion::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereDate('salida', $fecha)
                    ->count();
                
                // Solo incluir usuarios que tienen cotizaciones
                if ($cotizaciones_count > 0 || $usuario_id == $usuario->id) {
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'cotizaciones_count' => $cotizaciones_count,
                    ];
                }
            }

            // Filtrar por usuario específico si se solicita
            if ($usuario_id) {
                $resultado = array_filter($resultado, function($item) use ($usuario_id) {
                    return $item['id'] == $usuario_id;
                });
            }

            // Ordenar por total descendente
            usort($resultado, function($a, $b) {
                return $b['cotizaciones_count'] <=> $a['cotizaciones_count'];
            });

            return response()->json([
                'fecha' => $fecha,
                'total_usuarios' => count($resultado),
                'total_cotizaciones_dia' => array_sum(array_column($resultado, 'cotizaciones_count')),
                'usuarios' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reporte semanal de cotizaciones por usuario
     * Muestra cuántas cotizaciones creó cada usuario en la semana especificada
     */
    public function cotizacionesSemanales(Request $request): JsonResponse
    {
        try {
            $semana = $request->input('semana', Carbon::now()->weekOfYear);
            $anio = $request->input('anio', Carbon::now()->year);
            $usuario_id = $request->input('usuario_id');

            // Obtener usuarios con cotizaciones de la semana
            $usuarios = User::all();
            $resultado = [];

            foreach ($usuarios as $usuario) {
                $cotizaciones_count = Cotizacion::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereYear('salida', $anio)
                    ->whereRaw('WEEK(salida, 1) = ?', [$semana])
                    ->count();
                
                // Solo incluir usuarios que tienen cotizaciones
                if ($cotizaciones_count > 0 || $usuario_id == $usuario->id) {
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'cotizaciones_count' => $cotizaciones_count,
                    ];
                }
            }

            // Filtrar por usuario específico si se solicita
            if ($usuario_id) {
                $resultado = array_filter($resultado, function($item) use ($usuario_id) {
                    return $item['id'] == $usuario_id;
                });
            }

            // Ordenar por total descendente
            usort($resultado, function($a, $b) {
                return $b['cotizaciones_count'] <=> $a['cotizaciones_count'];
            });

            return response()->json([
                'semana' => $semana,
                'anio' => $anio,
                'total_usuarios' => count($resultado),
                'total_cotizaciones_semana' => array_sum(array_column($resultado, 'cotizaciones_count')),
                'usuarios' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reporte mensual de cotizaciones por usuario
     * Muestra cuántas cotizaciones creó cada usuario en el mes especificado
     */
    public function cotizacionesMensuales(Request $request): JsonResponse
    {
        try {
            $mes = $request->input('mes', Carbon::now()->month);
            $anio = $request->input('anio', Carbon::now()->year);
            $usuario_id = $request->input('usuario_id');

            // Obtener usuarios con cotizaciones del mes
            $usuarios = User::all();
            $resultado = [];

            foreach ($usuarios as $usuario) {
                $cotizaciones_count = Cotizacion::where('usuario_id', $usuario->id)
                    ->whereNotNull('salida')
                    ->whereYear('salida', $anio)
                    ->whereMonth('salida', $mes)
                    ->count();
                
                // Solo incluir usuarios que tienen cotizaciones
                if ($cotizaciones_count > 0 || $usuario_id == $usuario->id) {
                    $resultado[] = [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'nickname' => $usuario->nickname,
                        'email' => $usuario->email,
                        'cotizaciones_count' => $cotizaciones_count,
                    ];
                }
            }

            // Filtrar por usuario específico si se solicita
            if ($usuario_id) {
                $resultado = array_filter($resultado, function($item) use ($usuario_id) {
                    return $item['id'] == $usuario_id;
                });
            }

            // Ordenar por total descendente
            usort($resultado, function($a, $b) {
                return $b['cotizaciones_count'] <=> $a['cotizaciones_count'];
            });

            return response()->json([
                'mes' => $mes,
                'anio' => $anio,
                'total_usuarios' => count($resultado),
                'total_cotizaciones_mes' => array_sum(array_column($resultado, 'cotizaciones_count')),
                'usuarios' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
