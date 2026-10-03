<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contraintes et index pour les espaces familiaux / parents / comptes.
 * - Index sur eleves.espace_id (déjà présent ? on vérifie)
 * - Index sur parent_eleves.espace_id et is_principal
 * - Index sur comptes.espace_id, parent_id, email (unique)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_eleves', function (Blueprint $table) {
            $table->index('espace_id');
            $table->index(['espace_id', 'is_principal']);
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->index('espace_id');
            $table->index('parent_id');
            $table->index('email');
        });

        Schema::table('eleves', function (Blueprint $table) {
            $table->index('espace_id');
        });
    }

    public function down(): void
    {
        Schema::table('parent_eleves', function (Blueprint $table) {
            $table->dropIndex(['espace_id']);
            $table->dropIndex(['espace_id', 'is_principal']);
        });

        Schema::table('comptes', function (Blueprint $table) {
            $table->dropIndex(['espace_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['email']);
        });

        Schema::table('eleves', function (Blueprint $table) {
            $table->dropIndex(['espace_id']);
        });
    }
};