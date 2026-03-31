<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('usuario_data')->nullable();
            $table->json('cliente_data')->nullable();
            $table->string('fecha')->nullable();
            $table->json('fechas')->nullable();
            $table->string('servicio')->nullable();
            $table->string('mantenimiento')->nullable();
            $table->string('folio')->nullable();
            $table->string('idfolio')->nullable();
            $table->boolean('estatus')->default(false);
            $table->json('equipos')->nullable();
            $table->boolean('boton_deshabilitado')->default(false);
            $table->boolean('procesando_accion')->default(false);
            $table->timestamps();

            $table->index('firebase_id');
            $table->index('folio');
            $table->index('idfolio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
