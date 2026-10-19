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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('paquete_id')->constrained('paquetes');
            $table->dateTime('fecha_hora_registro')->useCurrent();
            $table->string('estado', 20);
            $table->string('situacion_de_pago', 30);
            $table->decimal('importe_total', 10, 2);
            $table->text('motivo_cancelacion')->nullable();
            $table->dateTime('fecha_hora_vencimiento')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
