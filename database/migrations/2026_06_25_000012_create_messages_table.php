<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages (emails) créés et envoyés depuis le module Communications.
 * statut : brouillon | en_cours | envoye | partiel | echec
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->string('sujet');
            $table->text('corps');
            $table->string('type')->nullable(); // relance, information, convocation...

            $table->foreignId('modele_id')->nullable()
                  ->constrained('modeles_messages')->nullOnDelete();

            $table->foreignId('expediteur_id')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->string('expediteur_nom')->nullable();

            $table->string('statut')->default('brouillon'); // brouillon|en_cours|envoye|partiel|echec
            $table->timestamp('date_envoi')->nullable();

            $table->integer('nb_destinataires')->default(0);
            $table->integer('nb_envoyes')->default(0);
            $table->integer('nb_echecs')->default(0);

            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index('statut');
            $table->index('date_envoi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};