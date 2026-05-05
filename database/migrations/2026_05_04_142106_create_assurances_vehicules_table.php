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
        Schema::create('assurances_vehicules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('voiture_id')->constrained('voitures')->onDelete('cascade');
    $table->string('compagnie_assurance');
    $table->string('numero_contrat')->unique();
    $table->date('date_debut');
    $table->date('date_fin');
    $table->decimal('prime', 12, 2);
    $table->string('type_assurance'); // tiers, tous risques, etc.
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
        Schema::dropIfExists('assurances_vehicules');
    }
};
