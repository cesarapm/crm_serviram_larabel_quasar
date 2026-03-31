<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'firebase_id',
        'usuario_id',
        'usuario_data',
        'contacto',
        'ciudad',
        'terminos',
        'pago',
        'folio_servicio',
        'telefono',
        'conceptos',
        'tiempo',
        'fechas',
        'salida',
        'compania',
        'folio',
        'direccion',
        'Nfolio',
        'area',
        'trabajo',
        'moneda',
        'boton_deshabilitado',
        'procesando_accion',
    ];

    protected $casts = [
        'usuario_data' => 'array',
        'conceptos' => 'array',
        'fechas' => 'array',
        'salida' => 'datetime',
        'boton_deshabilitado' => 'boolean',
        'procesando_accion' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}