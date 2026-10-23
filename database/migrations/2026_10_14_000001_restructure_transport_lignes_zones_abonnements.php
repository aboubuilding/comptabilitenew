<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transport scolaire :
 *  - une ligne est un parcours desservi par un chauffeur et un véhicule
 *    pendant une période (date_debut / date_fin) ; ses prix ne sont plus
 *    utilisés (les colonnes restent pour l'historique) ;
 *  - une zone appartient à une ligne (ligne_id) ; son tarif est indicatif ;
 *  - un abonnement bus reçoit la position GPS du domicile de l'élève, saisie
 *    après la souscription, et garde son origine (« reprise » quand il vient
 *    d'une souscription transport de la base historique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lignes', function (Blueprint $table) {
            // Présentes en production, absentes des migrations d'origine
            if (! Schema::hasColumn('lignes', 'chauffeur_id')) {
                $table->bigInteger('chauffeur_id')->nullable();
            }
            if (! Schema::hasColumn('lignes', 'voiture_id')) {
                $table->bigInteger('voiture_id')->nullable();
            }
            if (! Schema::hasColumn('lignes', 'date_debut')) {
                $table->date('date_debut')->nullable();
                $table->date('date_fin')->nullable();
            }
        });

        Schema::table('zones', function (Blueprint $table) {
            if (! Schema::hasColumn('zones', 'ligne_id')) {
                $table->unsignedBigInteger('ligne_id')->nullable()->after('couleur')->index();
            }
        });

        Schema::table('abonnements_bus', function (Blueprint $table) {
            if (! Schema::hasColumn('abonnements_bus', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('point_arret');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('abonnements_bus', 'origine')) {
                $table->string('origine', 20)->nullable()->after('statut');
            }
        });
    }

    public function down(): void
    {
        Schema::table('abonnements_bus', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'origine']);
        });
        Schema::table('zones', function (Blueprint $table) {
            $table->dropIndex(['ligne_id']);
            $table->dropColumn('ligne_id');
        });
        Schema::table('lignes', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
