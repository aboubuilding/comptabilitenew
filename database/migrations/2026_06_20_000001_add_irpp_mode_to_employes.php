<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            // Mode de calcul de l'IRPP : 'auto' (barème) ou 'manuel' (saisi dans retenues)
            $table->string('irpp_mode', 10)->default('auto')->after('mode_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropColumn('irpp_mode');
        });
    }
};