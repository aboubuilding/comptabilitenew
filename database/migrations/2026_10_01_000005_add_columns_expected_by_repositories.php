<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colonnes que le code interroge mais qu'aucune migration ne crée.
     */
    public function up(): void
    {
        // BaseRepository::activeQuery() filtre toujours sur etat = 1
        $tablesSansEtat = [
            'stock_actuel', 'abonnements_bus', 'assurances_vehicules',
            'carburant_vehicules', 'inscriptions_cantine', 'preparations_repas',
        ];

        foreach ($tablesSansEtat as $nom) {
            if (Schema::hasTable($nom) && !Schema::hasColumn($nom, 'etat')) {
                Schema::table($nom, function (Blueprint $table) {
                    $table->integer('etat')->default(1);
                });
            }
        }

        // Les tableaux de bord événements filtrent les participations par année
        if (!Schema::hasColumn('participation_evenements', 'annee_id')) {
            Schema::table('participation_evenements', function (Blueprint $table) {
                $table->bigInteger('annee_id')->nullable();
                $table->index('annee_id');
            });
        }

        // Les relations chargent users (id, name) : la table ne porte que nom / prenom.
        // Colonne calculée, jamais écrite par l'application.
        if (!Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name', 511)
                    ->virtualAs("TRIM(CONCAT(COALESCE(`nom`, ''), ' ', COALESCE(`prenom`, '')))");
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : etat / annee_id peuvent porter des données saisies depuis
    }
};
