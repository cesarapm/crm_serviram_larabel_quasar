<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuzonServicio extends Model
{
    protected $table = 'buzon_servicios';

    protected $fillable = [
        'firebase_id',
        'cliente_id',
        'orden_id',
        'agenda_id',
        'tecnico_id',
        'creado_por_id',
        'cliente_data',
        'servicio_descripcion',
        'tipo_equipo',
        'equipo_data',
        'fecha_solicitada',
        'hora_solicitada',
        'fechas',
        'prioridad',
        'tecnico_data',
        'notas',
        'estatus',
    ];

    protected $casts = [
        'cliente_data' => 'array',
        'equipo_data'  => 'array',
        'fechas'       => 'array',
        'tecnico_data' => 'array',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }
}
