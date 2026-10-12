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
        Schema::create('paquetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_programada_id')->constrained('salidas_programadas');
            $table->foreignId('refugio_id')->constrained('refugios');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_calculado', 10, 2);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paquetes');
    }
};
