<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    protected $fillable = [
        'firebase_id',
        'name',
        'mantenimiento',
    ];

    protected $casts = [
        'mantenimiento' => 'array',
    ];
}
