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
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();

            $table->string('email')->nullable();
            $table->string('mot_passe')->nullable();
            $table->string('statut_compte')->nullable();
            $table->integer('espace_id')->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('password_reset')->nullable();

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
        Schema::dropIfExists('comptes');
    }
};
