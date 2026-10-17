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
        Schema::create('salidas_programadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('excursion_id')->constrained('excursiones');
            $table->date('fecha_inicio');
            $table->time('hora_de_salida');
            $table->date('fecha_fin');
            $table->unsignedInteger('cupo_maximo');
            $table->unsignedInteger('cupo_disponible');
            $table->decimal('precio_base', 10, 2);
            $table->text('motivo_cancelacion')->nullable();
            $table->string('estado',20)->default('programada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salidas_programadas');
    }
};
