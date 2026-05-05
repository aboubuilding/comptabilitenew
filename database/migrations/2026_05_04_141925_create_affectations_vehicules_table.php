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
       Schema::create('affectations_vehicules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('voiture_id')->constrained('voitures')->onDelete('cascade');
    $table->foreignId('chauffeur_id')->nullable()->constrained('chauffeurs')->onDelete('set null');
    $table->date('date_debut');
    $table->date('date_fin')->nullable();
    $table->text('motif')->nullable();
    $table->tinyInteger('type_affectation')->default(1); // 1=permanente, 2=temporaire
    $table->bigInteger('annee_id')->nullable();
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
        Schema::dropIfExists('affectations_vehicules');
    }
};
