<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centres_analytiques', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('libelle', 150);
            $table->text('description')->nullable();
            $table->string('type', 20)->default('service'); // service | section | projet

            $table->bigInteger('responsable_id')->nullable();
            $table->bigInteger('annee_id')->nullable();
            $table->integer('etat')->default(1);
            $table->timestamps();

            $table->index('type');
            $table->index('annee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centres_analytiques');
    }
};
