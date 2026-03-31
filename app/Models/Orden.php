<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orden extends Model
{
    protected $table = 'ordenes';

    protected $fillable = [
        'firebase_id',
        'usuario_id',
        'usuario_data',
        'cliente_data',
        'fecha',
        'fechas',
        'servicio',
        'mantenimiento',
        'folio',
        'idfolio',
        'estatus',
        'equipos',
        'boton_deshabilitado',
        'procesando_accion',
    ];

    protected $casts = [
        'usuario_data' => 'array',
        'cliente_data' => 'array',
        'fechas' => 'array',
        'equipos' => 'array',
        'estatus' => 'boolean',
        'boton_deshabilitado' => 'boolean',
        'procesando_accion' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function agendaEventos(): HasMany
    {
        return $this->hasMany(Agenda::class, 'orden_id');
    }
}
