<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuloPermiso extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'modulo_permisos';

    protected $fillable = [
        'user_id',
        'modulo',
        'habilitado',
    ];

    protected $casts = [
        'habilitado' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
