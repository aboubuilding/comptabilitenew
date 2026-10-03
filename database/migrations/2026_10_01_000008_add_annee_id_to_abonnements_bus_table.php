<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AbonnementBusService renseigne l'année scolaire à la souscription et le
     * tableau de bord Transport filtre dessus, mais la colonne n'a jamais été créée.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('abonnements_bus', 'annee_id')) {
            Schema::table('abonnements_bus', function (Blueprint $table) {
                $table->bigInteger('annee_id')->nullable();
                $table->index('annee_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('abonnements_bus', 'annee_id')) {
            Schema::table('abonnements_bus', function (Blueprint $table) {
                $table->dropIndex(['annee_id']);
                $table->dropColumn('annee_id');
            });
        }
    }
};
