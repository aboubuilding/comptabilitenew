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
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->enum('type',[
                'excursion',
                'voyage',
                'sortie_pedagogique',
                'competition',
                'autre'
            ]);
            $table->date('date_evenement');
            $table->decimal('participation',15,2);
            $table->integer('capacite')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('annee_id')->nullable();
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
        Schema::dropIfExists('evenements');
    }
};
