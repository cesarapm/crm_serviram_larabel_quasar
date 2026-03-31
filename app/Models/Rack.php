<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'niveles',
        'capacidad',
        'posiciones_por_nivel',
        'firebase_id'
    ];

    protected $casts = [
        'niveles' => 'integer',
        'capacidad' => 'integer',
        'posiciones_por_nivel' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function items()
    {
        return $this->hasMany(Item::class, 'rack', 'nombre');
    }

    // Scopes
    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', $nombre);
    }

    public function scopePorUbicacion($query, $ubicacion)
    {
        return $query->where('ubicacion', 'like', "%{$ubicacion}%");
    }

    // Accessors
    public function getItemsActualesAttribute()
    {
        return $this->items()->count();
    }

    public function getCapacidadTotalAttribute()
    {
        return $this->niveles * $this->posiciones_por_nivel * $this->capacidad;
    }

    public function getPorcentajeOcupacionAttribute()
    {
        if ($this->capacidad_total == 0) {
            return 0;
        }
        return round(($this->items_actuales / $this->capacidad_total) * 100, 2);
    }

    public function getDisponibleAttribute()
    {
        return $this->capacidad_total - $this->items_actuales;
    }

    public function getEstadoAttribute()
    {
        $porcentaje = $this->porcentaje_ocupacion;
        
        if ($porcentaje >= 90) {
            return 'lleno';
        } elseif ($porcentaje >= 70) {
            return 'casi_lleno';
        } elseif ($porcentaje >= 30) {
            return 'medio';
        } else {
            return 'vacio';
        }
    }

    // Métodos de negocio
    public function puedeAgregarItems($cantidad = 1)
    {
        return $this->disponible >= $cantidad;
    }

    public function getItemsPorTipo()
    {
        return $this->items()
            ->selectRaw('tipo, count(*) as cantidad')
            ->groupBy('tipo')
            ->get();
    }

    public function getValorTotal()
    {
        return $this->items()
            ->selectRaw('SUM(stock * precio_unitario) as total')
            ->value('total') ?? 0;
    }

    // Validaciones
    public static function rules($id = null)
    {
        return [
            'nombre' => 'required|string|max:10|unique:racks,nombre,' . $id,
            'descripcion' => 'nullable|string|max:255',
            'ubicacion' => 'required|string|max:100',
            'niveles' => 'required|integer|min:1|max:20',
            'capacidad' => 'required|integer|min:1|max:100',
            'posiciones_por_nivel' => 'required|integer|min:1|max:20'
        ];
    }

    public static function messages()
    {
        return [
            'nombre.required' => 'El nombre del rack es obligatorio',
            'nombre.unique' => 'Ya existe un rack con este nombre',
            'nombre.max' => 'El nombre no puede tener más de 10 caracteres',
            'ubicacion.required' => 'La ubicación es obligatoria',
            'niveles.required' => 'Los niveles son obligatorios',
            'niveles.min' => 'Debe tener al menos 1 nivel',
            'capacidad.required' => 'La capacidad es obligatoria',
            'capacidad.min' => 'La capacidad debe ser mayor a 0',
            'posiciones_por_nivel.required' => 'Las posiciones por nivel son obligatorias'
        ];
    }
}