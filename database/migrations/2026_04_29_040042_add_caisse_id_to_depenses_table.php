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
            $table->foreignId('caisse_id')->nullable()->constrained('caisses')->nullOnDelete();
            $table->index('caisse_id'); // Performance pour les jointures/filtres
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropForeign(['caisse_id']);
            $table->dropIndex(['caisse_id']);
            $table->dropColumn('caisse_id');
        });
    }
};