<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'item_id',
        'user_id',
        'tipo_movimiento',
        'fecha',
        'responsable',
        'motivo',
        'referencia',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'observaciones',
        'referencia_tipo',
        'referencia_id',
        'firebase_id'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stock_anterior' => 'integer',
        'stock_nuevo' => 'integer',
        'fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Tipos de movimiento (soporte para valores antiguos y nuevos)
    public const TIPOS = [
        // Valores antiguos (minúsculas)
        'entrada' => 'Entrada',
        'salida' => 'Salida', 
        'ajuste' => 'Ajuste',
        'transferencia' => 'Transferencia',
        'devolucion' => 'Devolución',
        'perdida' => 'Pérdida',
        'inicial' => 'Stock Inicial',
        // Valores nuevos del frontend (capitalize)
        'Entrada' => 'Entrada',
        'Salida' => 'Salida',
        'Préstamo' => 'Préstamo',
        'Devolución' => 'Devolución',
        'Uso en Servicio' => 'Uso en Servicio',
        'Ajuste' => 'Ajuste'
    ];

    // Relaciones
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación polimórfica para la referencia (puede ser una orden, cotización, etc.)
    public function referencia()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePorItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    public function scopeEntradas($query)
    {
        return $query->whereIn('tipo_movimiento', ['entrada', 'devolucion', 'inicial']);
    }

    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo_movimiento', ['salida', 'transferencia', 'perdida']);
    }

    // Accessors
    public function getTipoMovimientoTextoAttribute()
    {
        return self::TIPOS[$this->tipo_movimiento] ?? $this->tipo_movimiento;
    }

    public function getEsEntradaAttribute()
    {
        return in_array($this->tipo_movimiento, ['entrada', 'devolucion', 'inicial']);
    }

    public function getEsSalidaAttribute()
    {
        return in_array($this->tipo_movimiento, ['salida', 'transferencia', 'perdida']);
    }

    public function getDiferenciaAttribute()
    {
        return $this->stock_nuevo - $this->stock_anterior;
    }

    // Métodos estáticos
    public static function registrarMovimiento($itemId, $tipoMovimiento, $cantidad, $stockAnterior, $stockNuevo, $observaciones = null, $userId = null, $referenciaType = null, $referenciaId = null)
    {
        return self::create([
            'item_id' => $itemId,
            'user_id' => $userId ?? auth()->id(),
            'tipo_movimiento' => $tipoMovimiento,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'observaciones' => $observaciones,
            'referencia_tipo' => $referenciaType,
            'referencia_id' => $referenciaId,
        ]);
    }

    public static function obtenerResumenPorItem($itemId, $fechaInicio = null, $fechaFin = null)
    {
        $query = self::porItem($itemId);
        
        if ($fechaInicio && $fechaFin) {
            $query->entreFechas($fechaInicio, $fechaFin);
        }

        return [
            'total_entradas' => $query->clone()->entradas()->sum('cantidad'),
            'total_salidas' => $query->clone()->salidas()->sum('cantidad'),
            'ultimo_movimiento' => $query->clone()->latest()->first(),
            'movimientos_por_tipo' => $query->clone()
                ->selectRaw('tipo_movimiento, count(*) as cantidad, sum(cantidad) as total')
                ->groupBy('tipo_movimiento')
                ->get()
        ];
    }

    // Validaciones
    public static function rules()
    {
        return [
            'item_id' => 'required|exists:items,id',
            'tipo_movimiento' => 'required|in:' . implode(',', array_keys(self::TIPOS)),
            'cantidad' => 'required|integer|min:1',
            'stock_anterior' => 'required|integer|min:0',
            'stock_nuevo' => 'required|integer|min:0',
            'observaciones' => 'nullable|string|max:500',
            'referencia_tipo' => 'nullable|string|max:100',
            'referencia_id' => 'nullable|integer'
        ];
    }

    public static function messages()
    {
        return [
            'item_id.required' => 'El item es obligatorio',
            'item_id.exists' => 'El item especificado no existe',
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio',
            'tipo_movimiento.in' => 'El tipo de movimiento no es válido',
            'cantidad.required' => 'La cantidad es obligatoria',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
            'stock_anterior.required' => 'El stock anterior es obligatorio',
            'stock_nuevo.required' => 'El stock nuevo es obligatorio'
        ];
    }
}