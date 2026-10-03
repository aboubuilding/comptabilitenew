<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // La table est désormais créée (version analytique) par 2026_01_15_000010
        if (Schema::hasTable('previsions')) {
            return;
        }

        Schema::create('previsions', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->enum('type', ['recette', 'depense']);
            $table->decimal('montant', 12, 2);
            $table->date('date_prevision');        // date de début de la période
            $table->date('date_fin')->nullable();  // fin de période (si applicable)
            $table->string('periode')->nullable(); // mois, trimestre, année
            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('categorie_id')->nullable(); // optionnel
            $table->text('commentaire')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index('annee_id');
            $table->index('type');
        });
    }

    public function down()
    {
        // Rien à faire : la table appartient à 2026_01_15_000010
    }
};