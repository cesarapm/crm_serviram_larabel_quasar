<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmServicio extends Model
{
    protected $fillable = [
        'firebase_id',
        'usuario_id',
        'equipo_id',
        'cliente_data',
        'status',
        'autorizacion',
        'servicio',
        'mantenimiento',
        'condicion',
        'actividad',
        'folio',
        'Nfolio',
        'visita',
        'tipo',
        'salida',
        'fechas',
        'conceptos',
        'ciclos',
        'frio',
        'tipoactividades',
        'boton_deshabilitado',
        'procesando_accion',
    ];

    protected $casts = [
        'cliente_data' => 'array',
        'fechas' => 'array',
        'conceptos' => 'array',
        'ciclos' => 'array',
        'frio' => 'array',
        'tipoactividades' => 'array',
        'salida' => 'datetime',
        'boton_deshabilitado' => 'boolean',
        'procesando_accion' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'equipo_id');
    }

    public function encuestasServicio(): HasMany
    {
        return $this->hasMany(EncuestaServicio::class, 'gm_servicio_id');
    }
}
