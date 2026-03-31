<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncuestaServicio extends Model
{
    protected $fillable = [
        'firebase_id',
        'origen',
        'servicio_firebase_id',
        'servicio_id',
        'gm_servicio_id',
        'calificacion',
        'fecha',
    ];

    protected $casts = [
        'calificacion' => 'float',
        'fecha' => 'datetime',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function gmServicio(): BelongsTo
    {
        return $this->belongsTo(GmServicio::class, 'gm_servicio_id');
    }
}
