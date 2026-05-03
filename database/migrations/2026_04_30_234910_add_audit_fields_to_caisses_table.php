<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            // 🔹 Champs pour tracer l'écart de clôture
            $table->decimal('ecart_constate', 15, 2)->nullable()
                ->after('solde_final')
                ->comment('Écart théorique - physique constaté à la clôture');
            
            $table->text('motif_ecart')->nullable()
                ->after('ecart_constate')
                ->comment('Explication de l\'écart (erreur, vol, oubli, etc.)');
            
            $table->bigInteger('validateur_ecart_id')->nullable()
                ->after('motif_ecart')
                ->comment('Utilisateur ayant validé l\'écart');
            
            $table->timestamp('date_validation_ecart')->nullable()
                ->after('validateur_ecart_id');
            
            $table->enum('statut_ecart', ['aucun', 'en_attente', 'valide', 'refuse', 'enquete'])
                ->default('aucun')
                ->after('date_validation_ecart')
                ->comment('Statut du traitement de l\'écart');
            
            // 🔹 Index pour les rapports d'audit
            $table->index(['statut_ecart', 'ecart_constate']);
        });
    }

    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            $table->dropColumn([
                'ecart_constate', 'motif_ecart', 'validateur_ecart_id',
                'date_validation_ecart', 'statut_ecart'
            ]);
        });
    }
};