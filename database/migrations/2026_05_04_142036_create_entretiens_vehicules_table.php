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
        Schema::create('entretiens_vehicules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('voiture_id')->constrained('voitures')->onDelete('cascade');
    $table->date('date_entretien');
    $table->string('type_entretien'); // vidange, révision, pneus, etc.
    $table->decimal('cout', 12, 2)->nullable();
    $table->integer('kilometrage');
    $table->text('observations')->nullable();
    $table->foreignId('effectue_par')->nullable()->constrained('chauffeurs');
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
        Schema::dropIfExists('entretiens_vehicules');
    }
};
