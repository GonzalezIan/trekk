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
        Schema::create('empleados_asignados', function (Blueprint $table) {
            $table->foreignId('salida_programada_id')->constrained('salidas_programadas')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados');
            $table->string('rol', 50);
            $table->primary(['salida_programada_id', 'empleado_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados_asignados');
    }
};
