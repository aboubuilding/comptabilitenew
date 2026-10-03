<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements_bus', function (Blueprint $table) {
            // Assignation : zone, véhicule, ligne, sens
            // zone_id (+ sa clé étrangère) est déjà posée par 2026_05_05_024656
            $zoneExiste = Schema::hasColumn('abonnements_bus', 'zone_id');
            if (!$zoneExiste) {
                $table->unsignedBigInteger('zone_id')->nullable()->after('inscription_id');
            }
            $table->unsignedBigInteger('voiture_id')->nullable()->after('zone_id');
            $table->unsignedBigInteger('ligne_id')->nullable()->after('voiture_id');

            // Sens de trajet : aller / retour / aller_retour
            $table->string('sens', 20)->default('aller_retour')->after('ligne_id');

            // Point d'arrêt / adresse de ramassage
            $table->string('point_arret', 200)->nullable()->after('sens');

            if (!$zoneExiste) {
                $table->foreign('zone_id')->references('id')->on('zones')->nullOnDelete();
            }
            $table->foreign('voiture_id')->references('id')->on('voitures')->nullOnDelete();
            $table->foreign('ligne_id')->references('id')->on('lignes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('abonnements_bus', function (Blueprint $table) {
            $table->dropForeign(['voiture_id']);
            $table->dropForeign(['ligne_id']);
            $table->dropColumn(['voiture_id', 'ligne_id', 'sens', 'point_arret']);
        });
    }
};