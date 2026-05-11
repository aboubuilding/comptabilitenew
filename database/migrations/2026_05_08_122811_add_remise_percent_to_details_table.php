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
        Schema::table('details', function (Blueprint $table) {
            if (!Schema::hasColumn('details', 'remise_percent')) {
                $table->decimal('remise_percent', 5, 2)->default(0)->after('montant');
            }
            // Index pour les colonnes fréquemment filtrées
            $table->index('type_paiement');
            $table->index('statut_paiement');
            $table->index('date_encaissement');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('details', function (Blueprint $table) {
            //
        });
    }
};
