<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            // Montants figés au moment du calcul
            $table->float('salaire_base')->nullable()->after('periode_id');
            $table->float('prime_anciennete')->nullable()->after('salaire_base');
            $table->float('sursalaire')->nullable()->after('prime_anciennete');
            $table->float('bonus')->nullable()->after('sursalaire');
            $table->float('logement')->nullable()->after('bonus');
            $table->float('nourriture')->nullable()->after('logement');
            $table->float('deplacement')->nullable()->after('nourriture');
            $table->float('autre_avantage')->nullable()->after('deplacement');

            // Totaux
            $table->float('total_brut')->nullable()->after('autre_avantage');
            $table->float('total_deductions')->nullable()->after('total_brut');
            $table->float('total_retenues')->nullable()->after('total_deductions');
            $table->float('net_a_payer')->nullable()->after('total_retenues');

            // Cycle de vie du bulletin
            $table->string('statut', 20)->default('brouillon')->after('net_a_payer'); // brouillon | valide | paye | annule
            $table->date('date_paiement')->nullable()->after('statut');
            $table->unsignedBigInteger('paye_par')->nullable()->after('date_paiement');
            $table->text('observations')->nullable()->after('paye_par');
            $table->string('reference', 50)->nullable()->after('observations');

            $table->foreign('paye_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('paies', function (Blueprint $table) {
            $table->dropForeign(['paye_par']);
            $table->dropColumn([
                'salaire_base', 'prime_anciennete', 'sursalaire', 'bonus',
                'logement', 'nourriture', 'deplacement', 'autre_avantage',
                'total_brut', 'total_deductions', 'total_retenues', 'net_a_payer',
                'statut', 'date_paiement', 'paye_par', 'observations', 'reference',
            ]);
        });
    }
};