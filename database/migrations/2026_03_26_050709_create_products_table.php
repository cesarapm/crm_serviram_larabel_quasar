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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->unique()->nullable();
            $table->string('nombre')->index();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('serie')->nullable();
            $table->string('linea')->nullable();
            $table->string('negocio')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('mantenimiento')->nullable();
            $table->integer('condicion')->default(1); // 1=Bueno, 2=Medio, 3=Mal
            $table->dateTime('ultima')->nullable(); // Última actualización/mantenimiento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
