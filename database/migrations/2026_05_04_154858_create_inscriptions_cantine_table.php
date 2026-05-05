<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inscriptions_cantine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->onDelete('cascade');
            $table->foreignId('frais_ecole_id')->nullable()->constrained('frais_ecoles')->onDelete('set null');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();      // calculé automatiquement (fin année scolaire)
            $table->decimal('montant_mensuel', 10, 2);
            $table->integer('nombre_mois')->nullable();
            $table->decimal('montant_total_du', 10, 2)->nullable();
            $table->tinyInteger('statut')->default(1); // 1=actif, 0=abandonné
            $table->date('date_abandon')->nullable();
            $table->text('motif_abandon')->nullable();
            $table->foreignId('abandonne_par')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('inscription_id');
            $table->index('statut');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inscriptions_cantine');
    }
};