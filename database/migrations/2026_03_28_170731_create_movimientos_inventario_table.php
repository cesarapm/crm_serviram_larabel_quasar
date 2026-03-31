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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('tipo_movimiento', ['entrada', 'salida', 'ajuste', 'transferencia', 'devolucion', 'perdida', 'inicial']);
            $table->integer('cantidad');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->text('observaciones')->nullable();
            $table->string('referencia_tipo')->nullable(); // Para relación polimórfica
            $table->unsignedBigInteger('referencia_id')->nullable(); // Para relación polimórfica
            $table->string('firebase_id')->nullable()->unique(); // Para migración desde Firebase
            $table->timestamps();
            
            // Índices
            $table->index(['item_id', 'created_at']);
            $table->index(['tipo_movimiento', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['referencia_tipo', 'referencia_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
