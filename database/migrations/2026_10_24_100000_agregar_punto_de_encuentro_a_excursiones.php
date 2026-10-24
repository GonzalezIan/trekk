<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF Notificar eventualidades - Recordatorio de salida: el correo informa el punto de encuentro,
 * que no estaba en el modelo. Se registra en la excursión.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excursiones', function (Blueprint $table) {
            $table->string('punto_de_encuentro', 255)->nullable()->after('equipamiento_obligatorio');
        });
    }

    public function down(): void
    {
        Schema::table('excursiones', function (Blueprint $table) {
            $table->dropColumn('punto_de_encuentro');
        });
    }
};
