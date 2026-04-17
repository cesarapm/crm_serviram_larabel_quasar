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
        Schema::table('buzon_servicios', function (Blueprint $table) {
            $table->string('hora_solicitada')->nullable()->after('fecha_solicitada');
        });
    }

    public function down(): void
    {
        Schema::table('buzon_servicios', function (Blueprint $table) {
            $table->dropColumn('hora_solicitada');
        });
    }
};
