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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('usuario_data')->nullable();
            $table->string('contacto')->nullable();
            $table->string('ciudad')->nullable();
            $table->text('terminos')->nullable();
            $table->text('pago')->nullable();
            $table->string('folio_servicio')->nullable();
            $table->string('telefono')->nullable();
            $table->json('conceptos')->nullable();
            $table->string('tiempo')->nullable();
            $table->json('fechas')->nullable();
            $table->timestamp('salida')->nullable();
            $table->string('compania')->nullable();
            $table->string('folio')->nullable();
            $table->text('direccion')->nullable();
            $table->string('Nfolio')->nullable();
            $table->string('area')->nullable();
            $table->text('trabajo')->nullable();
            $table->string('moneda', 10)->nullable();
            $table->boolean('boton_deshabilitado')->default(false);
            $table->boolean('procesando_accion')->default(false);
            $table->timestamps();

            $table->index('firebase_id');
            $table->index('folio');
            $table->index('Nfolio');
            $table->index('compania');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
