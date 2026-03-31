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
        Schema::create('encuesta_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable()->unique();
            $table->string('origen')->index(); // servicio | gm_servicio
            $table->string('servicio_firebase_id')->index();
            $table->unsignedBigInteger('servicio_id')->nullable()->index();
            $table->unsignedBigInteger('gm_servicio_id')->nullable()->index();
            $table->decimal('calificacion', 4, 2)->nullable();
            $table->timestamp('fecha')->nullable()->index();
            $table->timestamps();

            $table->foreign('servicio_id')->references('id')->on('servicios')->onDelete('set null');
            $table->foreign('gm_servicio_id')->references('id')->on('gm_servicios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuesta_servicios');
    }
};
