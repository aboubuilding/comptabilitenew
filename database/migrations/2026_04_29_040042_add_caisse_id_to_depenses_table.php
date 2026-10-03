<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            // Clé étrangère nullable (une dépense peut être créée sans caisse si besoin futur)
            // La colonne est déjà posée par 2026_01_12_000001 : on n'ajoute alors que l'index
            if (!Schema::hasColumn('depenses', 'caisse_id')) {
                $table->bigInteger('caisse_id')->nullable();
            }
            $table->index('caisse_id'); // Performance pour les jointures/filtres
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropIndex(['caisse_id']);
        });
    }
};