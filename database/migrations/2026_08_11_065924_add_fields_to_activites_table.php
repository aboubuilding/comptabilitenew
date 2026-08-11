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
        Schema::table('activites', function (Blueprint $table) {
            //
            $table->enum('type', [
                'sportive',
                'culturelle',
                'artistique',
                'scientifique',
                'linguistique',
                'technologique',
                'sociale',
                'autre'
            ])->nullable();
            $table->string('encadreur')->nullable();
            $table->string('contact_encadreur')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activites', function (Blueprint $table) {
            //

            // Supprimer les colonnes
            $table->dropColumn([
                'type',
                'encadreur',
                'contact_encadreur',

            ]);
        });
    }
};
