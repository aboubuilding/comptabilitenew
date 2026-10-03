<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_actuel', function (Blueprint $table) {
            $table->decimal('valeur_stock', 14, 2)->default(0)->after('quantite');
            $table->date('date_dernier_mouvement')->nullable()->after('valeur_stock');
        });
    }

    public function down(): void
    {
        Schema::table('stock_actuel', function (Blueprint $table) {
            $table->dropColumn(['valeur_stock', 'date_dernier_mouvement']);
        });
    }
};