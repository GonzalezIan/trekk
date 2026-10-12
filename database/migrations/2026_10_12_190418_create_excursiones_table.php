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
        Schema::create('excursiones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('dificultad', 20);
            $table->string('duracion', 50)->nullable();
            $table->text('recorrido')->nullable();
            $table->text('etapas')->nullable();
            $table->string('experiencia_requerida', 100)->nullable();
            $table->boolean('acepta_menores')->default(false);
            $table->text('equipamiento_obligatorio')->nullable();
            $table->boolean('requiere_certificado_medico')->default(false);
            $table->string('estado', 20)->default('activa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excursiones');
    }
};
