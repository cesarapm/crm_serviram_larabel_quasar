<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Las columnas fecha, responsable, motivo, referencia ya fueron agregadas
        // Solo modificar tipo_movimiento de ENUM a VARCHAR
        DB::statement("ALTER TABLE movimientos_inventario MODIFY COLUMN tipo_movimiento VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar el enum original
        DB::statement("ALTER TABLE movimientos_inventario MODIFY COLUMN tipo_movimiento ENUM('entrada', 'salida', 'ajuste', 'transferencia', 'devolucion', 'perdida', 'inicial') NOT NULL");
    }
};
