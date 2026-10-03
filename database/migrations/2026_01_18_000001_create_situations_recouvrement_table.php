<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('situations_recouvrement', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('inscription_id');
            $table->string('nature', 30);              // scolarite | cantine | bus | activite | autre
            $table->float('montant_du');               // total dû
            $table->float('montant_paye');             // total encaissé
            $table->float('retard');                   // montant_du - montant_paye (arrondi)
            $table->integer('jours_retard')->default(0); // ancienneté en jours
            $table->date('date_calcul');               // jour de la photo

            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->unique(['inscription_id', 'nature', 'date_calcul'], 'sit_unique_idx');
            $table->index(['annee_id', 'date_calcul']);
            $table->index(['inscription_id', 'nature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('situations_recouvrement');
    }
};