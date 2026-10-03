<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Sur employes : mode de paiement par défaut + coordonnées bancaires
        Schema::table('employes', function (Blueprint $table) {
            $table->string('mode_paiement', 20)->default('billette')->after('salaire');
            // 'billette' ou 'banque'

            $table->string('banque', 100)->nullable()->after('mode_paiement');
            $table->string('iban', 50)->nullable()->after('banque');
            $table->string('titulaire_compte', 150)->nullable()->after('iban');
        });

        // 2) Sur paies : trace du mode de paiement utilisé et du retrait effectif
        Schema::table('paies', function (Blueprint $table) {
            $table->string('mode_paiement', 20)->default('billette')->after('statut');
            // 'billette' ou 'banque'

            $table->string('banque', 100)->nullable()->after('mode_paiement');
            $table->string('iban', 50)->nullable()->after('banque');

            // Suivi du retrait effectif à la billetterie
            $table->boolean('retire_a_la_billetterie')->default(false)->after('date_paiement');
            $table->date('date_retrait')->nullable()->after('retire_a_la_billetterie');
            $table->unsignedBigInteger('retire_par')->nullable()->after('date_retrait');

            $table->foreign('retire_par')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropColumn(['mode_paiement', 'banque', 'iban', 'titulaire_compte']);
        });

        Schema::table('paies', function (Blueprint $table) {
            $table->dropForeign(['retire_par']);
            $table->dropColumn([
                'mode_paiement', 'banque', 'iban',
                'retire_a_la_billetterie', 'date_retrait', 'retire_par',
            ]);
        });
    }
};