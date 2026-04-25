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
        Schema::create('parent_eleves', function (Blueprint $table) {
            $table->id();

            $table->string('nom_parent')->nullable();
            $table->string('prenom_parent')->nullable();
            $table->string('telephone')->nullable();
            $table->string('profession')->nullable();
            $table->integer('espace_id')->nullable();
            $table->tinyInteger('is_principal')->nullable();
            $table->tinyInteger('role')->nullable();
            $table->integer('annee_id')->nullable();
            $table->integer('nationalite_id')->nullable();
            $table->string('whatsapp')->nullable();

            $table->string('quartier')->nullable();
            $table->text('adresse')->nullable();
            $table->string('email')->nullable();

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
        Schema::dropIfExists('parent_eleves');
    }
};
