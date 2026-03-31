<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agenda extends Model
{
    protected $table = 'agendas';

    protected $fillable = [
        'firebase_id',
        'orden_id',
        'id_orden_firebase',
        'start',
        'start_raw',
        'fecha',
        'all_day',
        'text_color',
        'title',
        'equipo_data',
        'block',
        'estatus',
    ];

    protected $casts = [
        'equipo_data' => 'array',
        'all_day' => 'boolean',
        'block' => 'boolean',
        'estatus' => 'boolean',
    ];

    protected function start(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $raw = trim((string) ($attributes['start_raw'] ?? ''));
                if ($raw !== '') {
                    return $raw;
                }

                $dbValue = trim((string) ($value ?? ''));
                if ($dbValue === '') {
                    return null;
                }

                try {
                    return Carbon::parse($dbValue)->format('Y-m-d\\TH:i:sP');
                } catch (\Throwable) {
                    return $dbValue;
                }
            },
            set: fn ($value) => $value
        );
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }
}
