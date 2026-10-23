<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une dépense est imputée à une ligne budgétaire (table `previsions`) : la
 * ligne connaît ainsi ce qui a été dépensé sur son montant global.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            if (! Schema::hasColumn('depenses', 'ligne_budgetaire_id')) {
                $table->unsignedBigInteger('ligne_budgetaire_id')->nullable()->after('montant')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropIndex(['ligne_budgetaire_id']);
            $table->dropColumn('ligne_budgetaire_id');
        });
    }
};
