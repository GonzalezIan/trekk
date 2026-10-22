<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El estado del certificado toma los valores del diagrama de clases
 * (PENDIENTE_DE_VERIFICACION | VALIDO | INVALIDO). "PENDIENTE_DE_VERIFICACION"
 * tiene 25 caracteres y no entraba en la columna de 20, y el valor por defecto
 * ("pendiente") no era ninguno de esos estados. Lo necesita el CU 17.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificados_medicos', function (Blueprint $table) {
            $table->string('estado', 30)->default('PENDIENTE_DE_VERIFICACION')->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificados_medicos', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->change();
        });
    }
};
