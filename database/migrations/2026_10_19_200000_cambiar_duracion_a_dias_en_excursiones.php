<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La duración pasa de texto libre ("5 horas") a cantidad de días (entero),
 * porque el CU 1 exige validar que sea de al menos un día (RN-02)
 * y Excursion::getDuracion() devuelve un int en el diagrama de clases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excursiones', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracion')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('excursiones', function (Blueprint $table) {
            $table->string('duracion', 50)->nullable()->change();
        });
    }
};
