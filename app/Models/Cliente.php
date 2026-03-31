<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'firebase_id',
        'compania',
        'contacto',
        'responsable',
        'telefono',
        'email',
        'ciudad',
        'direccion',
    ];
}
