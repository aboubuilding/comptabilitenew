<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sur une base neuve, clients est créée avec ventes (2024_06_13_195414)
        if (Schema::hasTable('clients')) {
            return;
        }

        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Personne morale ou physique
            $table->string('nom_raison_sociale', 200);
            $table->string('type_client', 20)->default('particulier'); // particulier | entreprise | institution

            // Contact
            $table->string('telephone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('adresse')->nullable();

            // Identité fiscale (optionnel)
            $table->string('nif', 50)->nullable();
            $table->string('rccm', 50)->nullable();

            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index('nom_raison_sociale');
            $table->index('type_client');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};