<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('previsions', function (Blueprint $table) {
            $table->id();

            $table->string('libelle', 150);
            $table->string('type', 20);                       // recette | depense
            $table->float('montant_previsionnel');
            $table->float('montant_realise')->nullable();     // peut être calculé

            // Rattachement analytique (facultatif : une prévision globale
            // peut ne viser aucun centre en particulier)
            $table->unsignedBigInteger('centre_analytique_id')->nullable();

            // Période : au choix, un des deux modes
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            $table->bigInteger('annee_id')->nullable();
            $table->bigInteger('cree_par')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index('annee_id');
            $table->index('type');
            $table->index('centre_analytique_id');

            $table->foreign('centre_analytique_id')
                ->references('id')->on('centres_analytiques')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previsions');
    }
};