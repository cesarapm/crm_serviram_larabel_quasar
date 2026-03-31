<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable()->index();
            
            // Relaciones opcionales (foreign keys)
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->unsignedBigInteger('equipo_id')->nullable()->index();
            
            // Cliente embebido (sin relación FK)
            $table->json('cliente_data')->nullable();
            
            // Campos principales
            $table->string('status')->default('Abierto')->index();
            $table->string('autorizacion')->nullable();
            $table->string('servicio')->nullable()->index();
            $table->string('mantenimiento')->nullable()->index();
            $table->string('condicion')->nullable();
            $table->text('actividad')->nullable();
            $table->string('folio')->nullable();
            $table->string('Nfolio')->nullable()->index();
            $table->string('visita')->nullable();
            $table->string('tipo')->nullable();
            
            // Fechas
            $table->timestamp('salida')->nullable();
            $table->json('fechas')->nullable();
            
            // Arrays JSON
            $table->json('conceptos')->nullable();
            $table->json('ciclos')->nullable();
            $table->json('frio')->nullable();
            $table->json('tipoactividades')->nullable();
            
            // Estados de UI
            $table->boolean('boton_deshabilitado')->default(false);
            $table->boolean('procesando_accion')->default(false);
            
            $table->timestamps();
            
            // Foreign keys opcionales
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('equipo_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
