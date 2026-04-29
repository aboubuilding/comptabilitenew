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
        Schema::table('depenses', function (Blueprint $table) {
            $table->bigInteger('validateur_id')->nullable()->after('utilisateur_id');
            $table->date('date_validation')->nullable()->after('validateur_id');
            $table->string('justificatif_demande')->nullable()->after('motif_depense');
            
            // ✅ Nouveau champ pour le motif de rejet
            $table->text('motif_rejet')->nullable()->after('date_validation')->comment('Motif en cas de rejet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropColumn(['validateur_id', 'date_validation', 'justificatif_demande', 'motif_rejet']);
        });
    }
};