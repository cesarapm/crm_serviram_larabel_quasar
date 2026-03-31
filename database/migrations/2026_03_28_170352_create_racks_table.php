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
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 10)->unique();
            $table->string('descripcion')->nullable();
            $table->string('ubicacion', 100);
            $table->integer('niveles')->default(1);
            $table->integer('capacidad')->default(1);
            $table->integer('posiciones_por_nivel')->default(1);
            $table->string('firebase_id')->nullable()->unique(); // Para migración desde Firebase
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('nombre');
            $table->index('ubicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
