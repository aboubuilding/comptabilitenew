<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignations_eleves_bus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonnement_bus_id')->constrained('abonnements_bus')->onDelete('cascade');
            $table->foreignId('voiture_id')->constrained('voitures')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->tinyInteger('sens')->default(1)->comment('1=aller,2=retour,3=aller-retour');
            $table->text('motif')->nullable();
            $table->tinyInteger('statut')->default(1); // 1=actif, 0=inactif
            $table->timestamps();

            $table->index(['abonnement_bus_id', 'voiture_id']);
            $table->index('zone_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignations_eleves_bus');
    }
};