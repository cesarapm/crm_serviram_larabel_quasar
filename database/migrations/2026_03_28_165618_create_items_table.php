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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->enum('tipo', ['Refacción', 'Insumo', 'Herramienta', 'Material', 'Otro']);
            $table->string('marca', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->string('unidad_medida', 20)->default('pzas');
            $table->string('rack', 10)->nullable();
            $table->string('ubicacion', 50)->nullable();
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->string('proveedor')->nullable();
            $table->string('firebase_id')->nullable()->unique(); // Para migración desde Firebase
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('codigo');
            $table->index('tipo');
            $table->index('rack');
            $table->index(['stock', 'stock_minimo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
