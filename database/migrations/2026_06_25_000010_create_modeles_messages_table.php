<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modèles de messages réutilisables (relance impayé, rentrée, convocation...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles_messages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('sujet');
            $table->text('corps'); // variables {{parent}}, {{eleve}}, {{classe}}...
            $table->json('variables_disponibles')->nullable();
            $table->string('type')->nullable(); // relance, information, convocation...
            $table->integer('etat')->default(1);
            $table->bigInteger('annee_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles_messages');
    }
};