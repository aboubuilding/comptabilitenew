<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('abonnements_bus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->onDelete('cascade');
            $table->date('date_debut');                 // début de l'abonnement (date d'inscription au bus)
            $table->date('date_fin')->nullable();       // calculé automatiquement (fin année scolaire)
            $table->decimal('montant_mensuel', 10, 2); // coût mensuel
            $table->integer('nombre_mois')->nullable(); // nombre de mois à payer (calculé)
            $table->decimal('montant_total_du', 10, 2)->nullable();
            $table->tinyInteger('statut')->default(1);  // 1=actif, 0=abandonné
            $table->date('date_abandon')->nullable();
            $table->text('motif_abandon')->nullable();
            $table->foreignId('abandonne_par')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('inscription_id');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonnements_bus');
    }
};