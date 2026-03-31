<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable();
            $table->foreignId('orden_id')->nullable()->constrained('ordenes')->nullOnDelete();
            $table->string('id_orden_firebase')->nullable();
            $table->timestamp('start')->nullable();
            $table->string('start_raw')->nullable();
            $table->string('fecha')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('text_color', 50)->nullable();
            $table->string('title')->nullable();
            $table->json('equipo_data')->nullable();
            $table->boolean('block')->default(false);
            $table->boolean('estatus')->default(false);
            $table->timestamps();

            $table->index('firebase_id');
            $table->index('id_orden_firebase');
            $table->index('start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
