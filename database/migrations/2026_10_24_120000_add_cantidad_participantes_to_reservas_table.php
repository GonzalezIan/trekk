<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CU 14 - Registrar reserva, paso 5 (RN-03): al ingresar la cantidad de participantes
 * se retienen esos cupos (y camas) mientras la reserva está "En curso", antes de que
 * se carguen los participantes. La reserva guarda la cantidad para poder liberarlos
 * si vence o el cliente cancela antes de pagar (17.1 / *a, RN-04).
 *
 * Las reservas que ya existían toman la cantidad de sus participantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->unsignedSmallInteger('cantidad_participantes')->default(0)->after('paquete_id');
        });

        DB::table('reservas')->update([
            'cantidad_participantes' => DB::raw(
                '(SELECT COUNT(*) FROM participantes WHERE participantes.reserva_id = reservas.id)'
            ),
        ]);
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('cantidad_participantes');
        });
    }
};
