<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agrega campos de Firebase y permisos a users.
     * Crea tabla modulo_permisos para gestionar permisos por módulo.
     */
    public function up(): void
    {
        // Agregar columnas a users
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_id')->nullable()->unique()->after('id');
            $table->string('nickname')->nullable()->unique()->after('name');
            $table->string('phone')->nullable()->after('password');
            $table->string('mobile')->nullable()->after('phone');
            $table->string('position')->nullable()->after('mobile');
            $table->boolean('activo')->default(true)->after('position');
            
            // Folios como columnas dedicadas
            $table->integer('Cfolio')->default(0)->after('activo');
            $table->integer('Dfolio')->default(0)->after('Cfolio');
            $table->integer('lastfolio')->default(0)->after('Dfolio');
        });

        // Generar nicknames para usuarios sin nickname
        $users = DB::table('users')
            ->select('id', 'name', 'email', 'nickname')
            ->orderBy('id')
            ->get();

        $assignedNicknames = DB::table('users')
            ->whereNotNull('nickname')
            ->pluck('nickname')
            ->filter()
            ->map(fn (string $nickname) => Str::lower($nickname))
            ->all();

        foreach ($users as $user) {
            if (!empty($user->nickname)) {
                continue;
            }

            $baseNickname = Str::of($user->email ?: $user->name)
                ->before('@')
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '')
                ->value();

            if ($baseNickname === '') {
                $baseNickname = 'user' . $user->id;
            }

            $nickname = $baseNickname;
            $suffix = 1;

            while (in_array(Str::lower($nickname), $assignedNicknames, true)) {
                $nickname = $baseNickname . $suffix;
                $suffix++;
            }

            $assignedNicknames[] = Str::lower($nickname);

            DB::table('users')
                ->where('id', $user->id)
                ->update(['nickname' => $nickname]);
        }

        // Crear tabla de módulo permisos
        Schema::create('modulo_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('modulo'); // orden, cliente, gmservicio, servicio, calendario, equipo, cotizacion, etc.
            $table->boolean('habilitado')->default(false);
            $table->timestamps();

            // Cada usuario tiene un registro único por módulo
            $table->unique(['user_id', 'modulo']);
            
            // Índice para búsquedas rápidas
            $table->index(['user_id', 'habilitado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulo_permisos');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['firebase_id']);
            $table->dropUnique(['nickname']);
            $table->dropColumn([
                'firebase_id',
                'nickname',
                'phone',
                'mobile',
                'position',
                'activo',
                'Cfolio',
                'Dfolio',
                'lastfolio',
            ]);
        });
    }
};
