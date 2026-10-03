<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * EntretienVehiculeRepository archive les entretiens comme le reste de
     * l'application (etat actif / inactif) : la table n'avait pas la colonne.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('entretiens_vehicules', 'etat')) {
            Schema::table('entretiens_vehicules', function (Blueprint $table) {
                $table->integer('etat')->default(1);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entretiens_vehicules', 'etat')) {
            Schema::table('entretiens_vehicules', function (Blueprint $table) {
                $table->dropColumn('etat');
            });
        }
    }
};
