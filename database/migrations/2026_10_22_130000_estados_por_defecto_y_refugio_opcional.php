<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los modelos, factories y seeders usan los estados en mayúscula (como en el
 * diagrama de clases), pero las migraciones originales dejaron los defaults en
 * minúscula. Una fila creada sin estado explícito quedaba, por ejemplo, en
 * "activa" y no coincidía con Excursion::ESTADO_ACTIVA.
 *
 * certificados_medicos no está acá: ya lo resolvió la migración
 * 2026_10_22_120000_ajustar_estado_en_certificados_medicos (CU 17).
 *
 * Además, refugio_id en paquetes pasa a ser opcional: un paquete puede no
 * incluir alojamiento (RN-04 del CU 7). La foreign key se mantiene.
 */
return new class extends Migration
{
    /** tabla => [default nuevo, default anterior] */
    private const ESTADOS = [
        'excursiones' => ['ACTIVA', 'activa'],
        'salidas_programadas' => ['PROGRAMADA', 'programada'],
        'paquetes' => ['DISPONIBLE', 'disponible'],
        'empleados' => ['ACTIVO', 'activo'],
    ];

    public function up(): void
    {
        foreach (self::ESTADOS as $tabla => [$nuevo]) {
            Schema::table($tabla, function (Blueprint $table) use ($nuevo) {
                $table->string('estado', 20)->default($nuevo)->change();
            });

            DB::table($tabla)->update(['estado' => DB::raw('UPPER(estado)')]);
        }

        Schema::table('paquetes', function (Blueprint $table) {
            $table->foreignId('refugio_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No se puede volver a NOT NULL si hay paquetes sin refugio.
        if (DB::table('paquetes')->whereNull('refugio_id')->exists()) {
            throw new RuntimeException(
                'Hay paquetes sin refugio_id; asignales un refugio antes de revertir esta migración.'
            );
        }

        Schema::table('paquetes', function (Blueprint $table) {
            $table->foreignId('refugio_id')->nullable(false)->change();
        });

        // Solo se restaura el default. Las filas quedan en mayúscula porque es
        // lo que espera el código; pasarlas a minúscula rompería la aplicación.
        foreach (self::ESTADOS as $tabla => [, $anterior]) {
            Schema::table($tabla, function (Blueprint $table) use ($anterior) {
                $table->string('estado', 20)->default($anterior)->change();
            });
        }
    }
};
