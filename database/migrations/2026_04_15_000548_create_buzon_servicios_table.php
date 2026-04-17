<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buzon_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('firebase_id')->nullable();

            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('orden_id')->nullable()->constrained('ordenes')->nullOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('agendas')->nullOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('cliente_data')->nullable();
            $table->text('servicio_descripcion')->nullable();
            $table->string('tipo_equipo')->nullable();
            $table->json('equipo_data')->nullable();
            $table->string('fecha_solicitada')->nullable();
            $table->json('fechas')->nullable();
            $table->enum('prioridad', ['alta', 'media', 'baja'])->default('media');
            $table->json('tecnico_data')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estatus', ['nuevo', 'en_revision', 'agendado', 'completado','rechazado'])->default('nuevo');

            $table->timestamps();

            $table->index('firebase_id');
            $table->index('estatus');
            $table->index('prioridad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buzon_servicios');
    }
};
