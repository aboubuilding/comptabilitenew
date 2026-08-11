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
        Schema::table('frais_ecoles', function (Blueprint $table) {
            $table->bigInteger('plan_echeancier_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('frais_ecoles', function (Blueprint $table) {


            // Supprimer la colonne
            $table->dropColumn('plan_echeancier_id');
        });
    }
};
