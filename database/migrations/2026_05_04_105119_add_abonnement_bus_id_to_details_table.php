<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('details', function (Blueprint $table) {
            if (!Schema::hasColumn('details', 'abonnement_bus_id')) {
                $table->foreignId('abonnement_bus_id')->nullable()
                      ->after('produit_id')
                      ->constrained('abonnements_bus')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('details', function (Blueprint $table) {
            $table->dropForeign(['abonnement_bus_id']);
            $table->dropColumn('abonnement_bus_id');
        });
    }
};