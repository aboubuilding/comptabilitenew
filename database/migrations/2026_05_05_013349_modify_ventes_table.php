<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventes', function (Blueprint $table) {
    // Ajout des colonnes pour l'entête
    $table->string('reference')->nullable();
   
    $table->decimal('total_ht', 12, 2)->nullable();
    $table->decimal('total_ttc', 12, 2)->nullable();
    $table->tinyInteger('type_vente')->default(1); // 1=vente au comptoir, 2=abonnement
    $table->tinyInteger('statut_paiement')->default(0); // 0=impayé, 1=payé, 2=annulé
    $table->bigInteger('client_id')->nullable()->constrained('clients'); // si clientèle externe

    // Supprimer les colonnes inutiles
    $table->dropColumn(['quantite', 'produit_id', 'detail_id']);
    $table->dropColumn('inscription_id'); // on garde ? plutot dans details (lié à un élève)
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
