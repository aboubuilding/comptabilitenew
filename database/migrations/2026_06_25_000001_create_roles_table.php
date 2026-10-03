<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel des profils / rôles applicatifs.
 * Correspond aux 15 profils identifiés dans le CDC v2.6 §3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // ex: ADMIN, COMPTABLE, CAISSIER...
            $table->string('libelle');               // ex: "Comptable"
            $table->string('description')->nullable();
            $table->integer('niveau')->default(0);   // 0 = plus élevé
            $table->integer('etat')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};