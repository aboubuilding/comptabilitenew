<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            // Justificatif (upload via FileUploadService)
            $table->string('justificatif_path')->nullable()->after('motif_depense');
            $table->string('justificatif_nom_original')->nullable()->after('justificatif_path');

            // Circuit de validation
            $table->bigInteger('valide_par')->nullable()->after('utilisateur_id');
            $table->datetime('date_validation')->nullable()->after('valide_par');
            $table->text('motif_rejet')->nullable()->after('date_validation');

            // Règlement via caisse (rattachement au moment du paiement)
            $table->bigInteger('caisse_id')->nullable()->after('motif_rejet');
            $table->datetime('date_reglement')->nullable()->after('caisse_id');
            $table->bigInteger('regle_par')->nullable()->after('date_reglement');
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropColumn([
                'justificatif_path', 'justificatif_nom_original',
                'valide_par', 'date_validation', 'motif_rejet',
                'caisse_id', 'date_reglement', 'regle_par',
            ]);
        });
    }
};