<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participation_evenements', function (Blueprint $table) {
            // Statut de la participation : pre_inscrit | confirme | annule
            $table->string('statut', 20)->default('pre_inscrit')->after('facture_id');

            // Colonne inscription_id propre (en plus de la colonne typo existante)
            if (! Schema::hasColumn('participation_evenements', 'inscription_id')) {
                $table->unsignedBigInteger('inscription_id')->nullable()->after('evenement_scolaire_id');
            }

            // Utilisateur qui a pré-inscrit / confirmé
            $table->unsignedBigInteger('utilisateur_id')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('participation_evenements', function (Blueprint $table) {
            $table->dropColumn(['statut', 'inscription_id', 'utilisateur_id']);
        });
    }
};