<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'firebase_id',
        'nombre',
        'marca',
        'modelo',
        'serie',
        'linea',
        'negocio',
        'ubicacion',
        'mantenimiento',
        'condicion',
        'ultima',
    ];

    protected $casts = [
        'condicion' => 'integer',
        'ultima' => 'datetime',
    ];
}
