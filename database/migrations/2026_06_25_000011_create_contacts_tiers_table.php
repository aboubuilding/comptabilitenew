<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Répertoire des tierces personnes destinataires d'une communication.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->string('organisation')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts_tiers');
    }
};