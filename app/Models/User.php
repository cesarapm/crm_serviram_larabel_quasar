<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    public const DEFAULT_MODULES = [
        'cliente',
        'actividades',
        'gmservicio',
        'allservicio',
        'calendario',
        'allgmservicio',
        'allcotizacion',
        'servicio',
        'orden',
        'equipo',
        'cotizacion',
        'admincalendario',
        'almacen',
        'buzon',
    ];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firebase_id',
        'name',
        'nickname',
        'email',
        'password',
        'phone',
        'mobile',
        'position',
        'activo',
        'Cfolio',
        'Dfolio',
        'lastfolio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'Cfolio' => 'integer',
            'Dfolio' => 'integer',
            'lastfolio' => 'integer',
        ];
    }

    public function moduloPermisos(): HasMany
    {
        return $this->hasMany(ModuloPermiso::class);
    }

    public function getModulePermissions(): array
    {
        $permissions = [];

        // Arrays todos los módulos por defecto en false
        foreach (self::DEFAULT_MODULES as $modulo) {
            $permissions[$modulo] = false;
        }

        if ($this->hasRole('admin')) {
            return array_fill_keys(self::DEFAULT_MODULES, true);
        }

        // Sobrescribir con valores de la base de datos
        $permisos = $this->moduloPermisos()->pluck('habilitado', 'modulo')->all();
        foreach ($permisos as $modulo => $habilitado) {
            $permissions[$modulo] = (bool) $habilitado;
        }

        return $permissions;
    }

    public function getFolioSettings(): array
    {
        return [
            'Cfolio' => (int) ($this->Cfolio ?? 0),
            'Dfolio' => (int) ($this->Dfolio ?? 0),
            'lastfolio' => (int) ($this->lastfolio ?? 0),
        ];
    }

    public function toLegacyPayload(): array
    {
        $permissions = $this->getModulePermissions();
        $folios = $this->getFolioSettings();

        return array_merge([
            'id' => $this->id,
            'firebase_id' => $this->firebase_id,
            'nombre' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'telefono' => $this->phone,
            'celular' => $this->mobile,
            'puesto' => $this->position,
            'activo' => $this->activo,
            'admin' => $this->hasRole('admin'),
        ], $permissions, $folios);
    }

    public function hasModuleAccess(string $module): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return (bool) data_get($this->getModulePermissions(), $module, false);
    }

    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }
}
