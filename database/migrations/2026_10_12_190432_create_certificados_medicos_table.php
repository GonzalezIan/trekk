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
        Schema::create('certificados_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participante_id')->constrained('participantes')->cascadeOnDelete();
            $table->date('fecha_emision')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('archivo', 255)->nullable();
            $table->dateTime('fecha_de_carga')->nullable();
            $table->date('fecha_de_vencimiento')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados_medicos');
    }
};
