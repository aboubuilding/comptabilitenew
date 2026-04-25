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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->nullable();
            $table->string('payeur')->nullable();
            $table->mediumText('motif_suppression')->nullable();
            $table->string('telephone_payeur')->nullable();
            $table->date('date_paiement')->nullable();
            $table->tinyInteger('statut_paiement')->nullable();
            $table->tinyInteger('mode_paiement')->nullable();
            $table->bigInteger('inscription_id')->nullable();
            $table->bigInteger('utilisateur_id')->nullable();
            $table->bigInteger('cheque_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->decimal('montant')->nullable();


            $table->integer('etat')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paiements');
    }
};
