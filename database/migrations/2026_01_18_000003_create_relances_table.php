<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('dossier_recouvrement_id');

            // appel | sms | email | courrier | mise_en_demeure
            $table->string('type_relance', 30);

            $table->date('date_relance');
            $table->text('contenu')->nullable();

            $table->float('montant_du_moment')->nullable();

            // sans_reponse | promesse_paiement | paiement_partiel | paiement_total | refus
            $table->string('resultat', 30)->nullable();

            $table->date('date_prochaine_relance')->nullable();

            $table->bigInteger('utilisateur_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable(); // lien Communications
            $table->bigInteger('annee_id')->nullable();

            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index(['dossier_recouvrement_id', 'date_relance']);
            $table->foreign('dossier_recouvrement_id')
                ->references('id')->on('dossiers_recouvrement')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relances');
    }
};