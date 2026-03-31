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
        Schema::create('gm_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable()->index();

            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->unsignedBigInteger('equipo_id')->nullable()->index();

            $table->json('cliente_data')->nullable();

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

            $table->timestamp('salida')->nullable();
            $table->json('fechas')->nullable();

            $table->json('conceptos')->nullable();
            $table->json('ciclos')->nullable();
            $table->json('frio')->nullable();
            $table->json('tipoactividades')->nullable();

            $table->boolean('boton_deshabilitado')->default(false);
            $table->boolean('procesando_accion')->default(false);

            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('equipo_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gm_servicios');
    }
};
