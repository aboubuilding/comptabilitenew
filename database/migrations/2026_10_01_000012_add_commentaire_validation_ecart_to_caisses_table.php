<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Validation des écarts de caisse par la Direction : statut_ecart,
     * valide_par et date_validation_ecart existent déjà ; il manque de quoi
     * consigner le commentaire de la décision (obligatoire en cas de refus).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('caisses', 'commentaire_validation_ecart')) {
            Schema::table('caisses', function (Blueprint $table) {
                $table->text('commentaire_validation_ecart')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('caisses', 'commentaire_validation_ecart')) {
            Schema::table('caisses', function (Blueprint $table) {
                $table->dropColumn('commentaire_validation_ecart');
            });
        }
    }
};
