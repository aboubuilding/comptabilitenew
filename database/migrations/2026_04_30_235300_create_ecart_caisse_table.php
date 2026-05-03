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
        Schema::create('ecart_caisse', function (Blueprint $table) {
    $table->id();
    $table->foreignId('caisse_id')->constrained('caisses');
    $table->decimal('ecart_constate', 15, 2);
    $table->enum('type', ['manquant', 'excedent']);
    $table->text('motif');
    $table->enum('statut', ['en_attente', 'valide', 'refuse', 'enquete']);
    $table->foreignId('declare_par')->constrained('users');
    $table->foreignId('valide_par')->nullable()->constrained('users');
    $table->timestamp('date_cloture');
    $table->timestamp('date_validation')->nullable();
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
        Schema::dropIfExists('ecart_caisse');
    }
};
