<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'marca',
        'descripcion',
        'stock',
        'stock_minimo',
        'unidad_medida',
        'rack',
        'ubicacion',
        'precio_unitario',
        'proveedor',
        'firebase_id'
    ];

    protected $casts = [
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'precio_unitario' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Tipos disponibles para el item
    public const TIPOS = [
        'Refacción',
        'Insumo',
        'Herramienta',
        'Material',
        'Otro'
    ];

    // Relaciones
    public function rackRelacion()
    {
        return $this->belongsTo(Rack::class, 'rack', 'nombre');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class)->latest();
    }

    public function ultimoMovimiento()
    {
        return $this->hasOne(MovimientoInventario::class)->latest();
    }

    // Scopes
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeBajoStock($query)
    {
        return $query->whereRaw('stock <= stock_minimo');
    }

    public function scopePorRack($query, $rack)
    {
        return $query->where('rack', $rack);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
                ->orWhere('nombre', 'like', "%{$termino}%")
                ->orWhere('descripcion', 'like', "%{$termino}%")
                ->orWhere('marca', 'like', "%{$termino}%");
        });
    }

    // Accesorios
    public function getStockStatusAttribute()
    {
        if ($this->stock <= 0) {
            return 'sin_stock';
        } elseif ($this->stock <= $this->stock_minimo) {
            return 'bajo_stock';
        } else {
            return 'normal';
        }
    }

    public function getValorTotalAttribute()
    {
        return $this->stock * $this->precio_unitario;
    }

    // Métodos de negocio para manejo de stock
    public function ajustarStock($tipoMovimiento, $cantidad, $observaciones = null, $userId = null, $referenciaType = null, $referenciaId = null)
    {
        $stockAnterior = $this->stock;
        
        switch ($tipoMovimiento) {
            case 'entrada':
            case 'devolucion':
                $this->stock += $cantidad;
                break;
            case 'salida':
            case 'transferencia':
            case 'perdida':
                $this->stock = max(0, $this->stock - $cantidad);
                break;
            case 'ajuste':
            case 'inicial':
                $this->stock = $cantidad;
                $cantidad = abs($cantidad - $stockAnterior); // Calcular diferencia
                break;
        }

        $this->save();

        // Registrar movimiento
        MovimientoInventario::registrarMovimiento(
            $this->id,
            $tipoMovimiento,
            $cantidad,
            $stockAnterior,
            $this->stock,
            $observaciones,
            $userId,
            $referenciaType,
            $referenciaId
        );

        return $this;
    }

    public function entradaStock($cantidad, $observaciones = null, $userId = null)
    {
        return $this->ajustarStock('entrada', $cantidad, $observaciones, $userId);
    }

    public function salidaStock($cantidad, $observaciones = null, $userId = null)
    {
        if ($cantidad > $this->stock) {
            throw new \Exception("No hay suficiente stock. Stock actual: {$this->stock}, solicitado: {$cantidad}");
        }
        return $this->ajustarStock('salida', $cantidad, $observaciones, $userId);
    }

    public function establecerStockInicial($cantidad, $observaciones = 'Stock inicial', $userId = null)
    {
        return $this->ajustarStock('inicial', $cantidad, $observaciones, $userId);
    }

    // Validaciones
    public static function rules($id = null)
    {
        return [
            'codigo' => 'required|string|max:50|unique:items,codigo,' . $id,
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:' . implode(',', self::TIPOS),
            'marca' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'unidad_medida' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:10',
            'ubicacion' => 'nullable|string|max:50',
            'precio_unitario' => 'nullable|numeric|min:0',
            'proveedor' => 'nullable|string|max:255'
        ];
    }

    public static function messages()
    {
        return [
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya está en uso',
            'nombre.required' => 'El nombre es obligatorio',
            'tipo.required' => 'El tipo es obligatorio',
            'tipo.in' => 'El tipo debe ser uno de los valores permitidos',
            'stock.required' => 'El stock es obligatorio',
            'stock.integer' => 'El stock debe ser un número entero',
            'stock.min' => 'El stock no puede ser negativo',
            'stock_minimo.required' => 'El stock mínimo es obligatorio',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo',
            'precio_unitario.numeric' => 'El precio debe ser un número',
            'precio_unitario.min' => 'El precio no puede ser negativo'
        ];
    }
}