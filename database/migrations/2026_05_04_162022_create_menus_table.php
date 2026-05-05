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
        Schema::create('menus', function (Blueprint $table) {
    $table->id();
    $table->string('libelle');
    $table->text('description')->nullable();
    $table->date('date_service')->nullable(); // date à laquelle ce menu est servi
    $table->tinyInteger('type_repas')->default(1); // 1=déjeuner, 2=goûter, etc.
    $table->integer('quantite_prevue')->default(0); // nombre de parts prévues
    $table->integer('quantite_reellement')->nullable(); // nombre de parts réellement servies
    $table->decimal('cout_total_prevu', 12, 2)->nullable();
    $table->decimal('cout_total_reel', 12, 2)->nullable();
    $table->foreignId('inscription_cantine_id')->nullable()->constrained('inscriptions_cantine')->nullOnDelete(); // optionnel : menu personnalisé par inscription ?
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
        Schema::dropIfExists('menus');
    }
};
