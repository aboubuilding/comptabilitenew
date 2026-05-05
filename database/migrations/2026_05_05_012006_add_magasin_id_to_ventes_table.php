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
    if (!Schema::hasColumn('ventes', 'magasin_id')) {
        $table->foreignId('magasin_id')->nullable()->after('produit_id')->constrained('magasins');
    }
    // Optionnel : préciser unité de vente
    if (!Schema::hasColumn('ventes', 'unite')) {
        $table->string('unite')->nullable()->after('quantite');
    }
    if (!Schema::hasColumn('ventes', 'prix_unitaire')) {
        $table->decimal('prix_unitaire', 12, 2)->nullable()->after('quantite');
    }
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventes', function (Blueprint $table) {
            //
        });
    }
};
