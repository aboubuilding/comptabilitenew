<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversements_ventes', function (Blueprint $table) {
            $table->id();

            $table->string('reference', 50)->unique();
            $table->date('date_reversement');

            $table->unsignedBigInteger('magasin_id');            // Boutique source
            $table->unsignedBigInteger('caisse_id')->nullable(); // Caisse qui reçoit

            $table->decimal('montant_declare', 12, 2);           // Saisi par le gérant
            $table->decimal('montant_compte', 12, 2)->nullable(); // Compté par le caissier
            $table->decimal('ecart', 12, 2)->nullable();

            $table->text('commentaire')->nullable();
            $table->text('motif_ecart')->nullable();

            $table->bigInteger('utilisateur_id')->nullable(); // Qui a saisi
            $table->bigInteger('caissier_id')->nullable();    // Qui a validé
            $table->datetime('date_validation')->nullable();

            // en_attente | valide | rejete
            $table->string('statut', 20)->default('en_attente');

            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index(['date_reversement', 'magasin_id']);
            $table->index('statut');

            $table->foreign('magasin_id')->references('id')->on('magasins')->onDelete('restrict');
        });

        // Ajout de la FK ventes.reversement_id -> reversements_ventes.id (si supporté)
        // On garde bigInteger simple pour compatibilité multi-SGBD.
    }

    public function down(): void
    {
        Schema::dropIfExists('reversements_ventes');
    }
};