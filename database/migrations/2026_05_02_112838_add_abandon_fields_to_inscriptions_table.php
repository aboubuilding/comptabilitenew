<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            // Ajout des colonnes pour la gestion des abandons
            if (!Schema::hasColumn('inscriptions', 'date_abandon')) {
                $table->date('date_abandon')->nullable()->after('date_inscription');
            }
            if (!Schema::hasColumn('inscriptions', 'motif_abandon')) {
                $table->text('motif_abandon')->nullable()->after('date_abandon');
            }
            if (!Schema::hasColumn('inscriptions', 'statut_abandon')) {
                $table->tinyInteger('statut_abandon')->default(0)->comment('0=actif,1=abandonné')->after('motif_abandon');
            }
            if (!Schema::hasColumn('inscriptions', 'abandonne_par')) {
                $table->bigInteger('abandonne_par')->nullable();
                
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropForeign(['abandonne_par']);
            $table->dropColumn(['date_abandon', 'motif_abandon', 'statut_abandon', 'abandonne_par']);
        });
    }
};
