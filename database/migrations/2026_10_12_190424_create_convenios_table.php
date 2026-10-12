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
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refugio_id')->constrained('refugios')->cascadeOnDelete();
            $table->string('nombre_convenio', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedInteger('camas_totales');
            $table->unsignedInteger('camas_disponibles');
            $table->decimal('precio_cama', 10, 2);
            $table->timestamps();
            $table->unique(['refugio_id', 'nombre_convenio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
