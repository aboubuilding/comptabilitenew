<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 12, 2)->nullable()->after('quantite');
            $table->decimal('valeur_mouvement', 14, 2)->nullable()->after('prix_unitaire');
            $table->string('libelle', 200)->nullable()->after('type_mouvement');
            $table->text('motif')->nullable()->after('libelle');
            $table->unsignedBigInteger('utilisateur_id')->nullable()->after('motif');
            // Source polymorphe pour lier aux bons d'achat, transferts, ventes...
            $table->string('source_type', 120)->nullable()->after('utilisateur_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'prix_unitaire', 'valeur_mouvement',
                'libelle', 'motif',
                'utilisateur_id', 'source_type', 'source_id',
            ]);
        });
    }
};