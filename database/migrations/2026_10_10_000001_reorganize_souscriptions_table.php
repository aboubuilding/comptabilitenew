<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les souscriptions ne portent plus que les services aux élèves : cantine,
 * transport, activités extrascolaires et événements.
 *
 *  - `origine` dit d'où vient la ligne : « reprise » (chargée depuis la base
 *    historique par `php artisan souscriptions:recharger`), « saisie » (écran
 *    Souscriptions), ou le module qui la porte (cantine, bus, activite,
 *    evenement) avec son identifiant dans `origine_id` ;
 *  - `libelle` est le nom affiché (Cantine, Transport, nom de l'activité...) ;
 *  - le montant passe en décimal (un float arrondit au-delà de 16 millions) ;
 *  - `ligne_id` et `zone_id`, jamais renseignés, disparaissent ;
 *  - `souscriptions_archive` garde les lignes d'avant le rechargement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('souscriptions', function (Blueprint $table) {
            $table->string('origine', 20)->nullable()->after('type_paiement');
            $table->unsignedBigInteger('origine_id')->nullable()->after('origine');
            $table->string('libelle')->nullable()->after('origine_id');
            $table->unsignedBigInteger('activite_id')->nullable()->after('frais_ecole_id');
            $table->unsignedBigInteger('evenement_id')->nullable()->after('activite_id');
            $table->index(['type_paiement', 'annee_id'], 'souscriptions_type_annee_idx');
            $table->index(['origine', 'origine_id'], 'souscriptions_origine_idx');
        });

        DB::statement('ALTER TABLE souscriptions MODIFY montant_annuel_prevu DECIMAL(12,2) NULL');

        foreach (['ligne_id', 'zone_id'] as $colonne) {
            if (Schema::hasColumn('souscriptions', $colonne)) {
                Schema::table('souscriptions', fn (Blueprint $table) => $table->dropColumn($colonne));
            }
        }

        if (! Schema::hasTable('souscriptions_archive')) {
            Schema::create('souscriptions_archive', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->date('date_souscription')->nullable();
                $table->decimal('montant_annuel_prevu', 12, 2)->nullable();
                $table->double('taux_remise')->nullable();
                $table->tinyInteger('type_paiement')->nullable();
                $table->bigInteger('frais_ecole_id')->nullable();
                $table->bigInteger('niveau_id')->nullable();
                $table->bigInteger('annee_id')->nullable();
                $table->bigInteger('inscription_id')->nullable();
                $table->bigInteger('utilisateur_id')->nullable();
                $table->integer('etat')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('archive_le')->nullable();
            });
        }

        if (! collect(DB::select("SHOW INDEX FROM details WHERE Key_name = 'details_souscription_id_idx'"))->count()) {
            Schema::table('details', fn (Blueprint $table) => $table->index('souscription_id', 'details_souscription_id_idx'));
        }
    }

    public function down(): void
    {
        Schema::table('details', fn (Blueprint $table) => $table->dropIndex('details_souscription_id_idx'));
        Schema::dropIfExists('souscriptions_archive');

        Schema::table('souscriptions', function (Blueprint $table) {
            $table->dropIndex('souscriptions_type_annee_idx');
            $table->dropIndex('souscriptions_origine_idx');
            $table->dropColumn(['origine', 'origine_id', 'libelle', 'activite_id', 'evenement_id']);
            $table->bigInteger('ligne_id')->nullable();
            $table->bigInteger('zone_id')->nullable();
        });

        DB::statement('ALTER TABLE souscriptions MODIFY montant_annuel_prevu FLOAT NULL');
    }
};
