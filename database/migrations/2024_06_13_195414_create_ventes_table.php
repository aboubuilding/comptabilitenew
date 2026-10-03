<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table clients créée d'abord (dépendance de ventes.client_id)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom_raison_sociale', 200);
            $table->string('type_client', 20)->default('particulier');
            $table->string('telephone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('adresse')->nullable();
            $table->string('nif', 50)->nullable();
            $table->string('rccm', 50)->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index('nom_raison_sociale');
            $table->index('type_client');
        });

        // Table ventes — version consolidée
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('reference', 50)->nullable();
            $table->date('date_vente')->nullable();

            // Montants
            $table->decimal('total_ht', 12, 2)->nullable();
            $table->decimal('total_ttc', 12, 2)->nullable();

            // Type et statut
            $table->tinyInteger('type_vente')->default(1);      // 1=comptoir, 2=abonnement
            $table->tinyInteger('statut_paiement')->default(0); // 0=impayé, 1=payé, 2=annulé

            // Rattachements
            // magasins est créée plus tard (2024_07_13) : la clé étrangère est posée
            // par 2026_10_01_000001_align_legacy_schema
            $table->foreignId('magasin_id')->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->bigInteger('inscription_id')->nullable();
            $table->bigInteger('paiement_id')->nullable();

            // Reversement (boutique sans ordinateur)
            $table->date('date_reversement')->nullable();
            $table->bigInteger('reversement_id')->nullable();
            $table->bigInteger('caissier_id')->nullable();

            $table->bigInteger('utilisateur_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index(['annee_id', 'date_vente']);
            $table->index('statut_paiement');
            $table->index('reference');
            $table->index('reversement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
        Schema::dropIfExists('clients');
    }
};