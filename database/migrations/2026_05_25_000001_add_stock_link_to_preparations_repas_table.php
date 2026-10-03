<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preparations_repas', function (Blueprint $table) {
            $table->unsignedBigInteger('magasin_id')->nullable()->after('menu_id');
            $table->boolean('stock_decremente')->default(false)->after('cout_reel');
            $table->bigInteger('annee_id')->nullable()->after('responsable_id');

            $table->foreign('magasin_id')->references('id')->on('magasins')->nullOnDelete();
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedBigInteger('magasin_id')->nullable()->after('type_repas');
            $table->foreign('magasin_id')->references('id')->on('magasins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('preparations_repas', function (Blueprint $table) {
            $table->dropForeign(['magasin_id']);
            $table->dropColumn(['magasin_id', 'stock_decremente', 'annee_id']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['magasin_id']);
            $table->dropColumn('magasin_id');
        });
    }
};