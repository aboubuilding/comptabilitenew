<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Index sur les clés qui servent à retrouver les paiements et les engagements
 * d'un élève. Sans eux, chaque écran de saisie, chaque encaissement et le
 * recalcul du recouvrement parcouraient les tables entières.
 */
return new class extends Migration
{
    private const INDEX = [
        'details'                  => ['inscription_id', 'paiement_id', 'caisse_id'],
        'paiements'                => ['inscription_id', 'annee_id'],
        'souscriptions'            => ['inscription_id'],
        'participation_evenements' => ['inscription_id', 'evenement_scolaire_id'],
        'mouvements'               => ['caisse_id', 'paiement_id'],
    ];

    public function up(): void
    {
        foreach (self::INDEX as $table => $colonnes) {
            foreach ($colonnes as $colonne) {
                if (! Schema::hasColumn($table, $colonne) || $this->estIndexee($table, $colonne)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($colonne, "{$table}_{$colonne}_idx"));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEX as $table => $colonnes) {
            foreach ($colonnes as $colonne) {
                $nom = "{$table}_{$colonne}_idx";
                $existe = DB::table('information_schema.statistics')
                    ->where('table_schema', DB::getDatabaseName())
                    ->where('table_name', $table)
                    ->where('index_name', $nom)
                    ->exists();

                if ($existe) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($nom));
                }
            }
        }
    }

    /** Vrai si la colonne est déjà la première d'un index. */
    private function estIndexee(string $table, string $colonne): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $colonne)
            ->where('seq_in_index', 1)
            ->exists();
    }
};
