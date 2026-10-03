<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profils complémentaires d'un utilisateur.
     *
     * Cahier des charges § 3 : « un même utilisateur physique pourra, selon la
     * taille de l'établissement, cumuler plusieurs profils (par exemple, le
     * Comptable peut assurer également le recouvrement) ». Le profil principal
     * reste porté par users.role ; cette table porte ceux qui s'y ajoutent.
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
