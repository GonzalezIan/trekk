<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU 1 y CU 3: el Administrativo puede cargar la imagen de la excursión.
 * Es opcional: las excursiones sin imagen siguen mostrando una foto genérica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excursiones', function (Blueprint $table) {
            $table->string('imagen', 255)->nullable()->after('punto_de_encuentro');
        });
    }

    public function down(): void
    {
        Schema::table('excursiones', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
