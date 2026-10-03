<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_bons', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 12, 2)->nullable()->after('quantite');
            $table->decimal('montant', 14, 2)->nullable()->after('prix_unitaire');
        });
    }

    public function down(): void
    {
        Schema::table('detail_bons', function (Blueprint $table) {
            $table->dropColumn(['prix_unitaire', 'montant']);
        });
    }
};