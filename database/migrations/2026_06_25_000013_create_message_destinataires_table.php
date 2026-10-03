<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destinataires d'un message + statut de livraison individuel.
 * destinataire_type : parent | eleve | tiers | libre | groupe
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_destinataires', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();

            $table->string('destinataire_type')->nullable(); // parent|eleve|tiers|libre|groupe
            $table->bigInteger('destinataire_id')->nullable();
            $table->string('nom')->nullable();
            $table->string('email');
            $table->string('statut_envoi')->default('en_attente'); // en_attente|envoye|echec|ouvert
            $table->text('erreur')->nullable();
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_ouverture')->nullable();

            $table->timestamps();

            $table->index(['message_id', 'statut_envoi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_destinataires');
    }
};