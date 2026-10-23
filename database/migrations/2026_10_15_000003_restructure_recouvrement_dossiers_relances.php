<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recouvrement :
 *  - un dossier par élève ; ses échéances réclamées (toutes natures : scolarité,
 *    cantine, transport) sont reprises à chaque recalcul ;
 *  - un dossier clôturé garde le paiement qui l'a soldé ;
 *  - une relance est un message (email ou SMS) à un destinataire, envoyé
 *    individuellement ou en lot ; elle peut viser un élève sans dossier
 *    (relance préventive depuis la planification).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dossier_recouvrement_echeances')) {
            Schema::create('dossier_recouvrement_echeances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dossier_recouvrement_id')->index();
                $table->string('nature', 30)->index();
                $table->string('libelle');
                $table->date('date_echeance')->nullable();
                $table->decimal('montant', 12, 2)->default(0);
                $table->decimal('montant_paye', 12, 2)->default(0);
                $table->decimal('reste', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('dossiers_recouvrement', function (Blueprint $table) {
            if (! Schema::hasColumn('dossiers_recouvrement', 'paiement_cloture_id')) {
                $table->unsignedBigInteger('paiement_cloture_id')->nullable()->after('motif_cloture');
            }
        });

        Schema::table('relances', function (Blueprint $table) {
            if (! Schema::hasColumn('relances', 'inscription_id')) {
                $table->unsignedBigInteger('inscription_id')->nullable()->after('dossier_recouvrement_id')->index();
                $table->string('destinataire')->nullable()->after('contenu');
                $table->string('statut_envoi', 20)->default('a_envoyer')->after('destinataire');
                $table->string('lot', 40)->nullable()->after('statut_envoi')->index();
            }
            $table->unsignedBigInteger('dossier_recouvrement_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('relances', function (Blueprint $table) {
            $table->dropIndex(['inscription_id']);
            $table->dropIndex(['lot']);
            $table->dropColumn(['inscription_id', 'destinataire', 'statut_envoi', 'lot']);
        });
        Schema::table('dossiers_recouvrement', function (Blueprint $table) {
            $table->dropColumn('paiement_cloture_id');
        });
        Schema::dropIfExists('dossier_recouvrement_echeances');
    }
};
