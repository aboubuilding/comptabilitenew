<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * $table->float() produit un DOUBLE(8,2), plafonné à 999 999,99 : une
     * scolarité, un salaire ou un solde de caisse dépassant le million de
     * francs est alors refusé par la base.
     *
     * On retire la précision de toutes les colonnes concernées (DOUBLE simple),
     * en conservant nullabilité, valeur par défaut et commentaire. Les valeurs
     * existantes ne changent pas.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $colonnes = DB::select(
            "SELECT c.TABLE_NAME AS table_name, c.COLUMN_NAME AS column_name, c.IS_NULLABLE AS is_nullable,
                    c.COLUMN_DEFAULT AS column_default, c.COLUMN_COMMENT AS column_comment
               FROM information_schema.COLUMNS c
               JOIN information_schema.TABLES t
                 ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
              WHERE c.TABLE_SCHEMA = DATABASE()
                AND t.TABLE_TYPE = 'BASE TABLE'
                AND c.COLUMN_TYPE = 'double(8,2)'
                AND c.GENERATION_EXPRESSION = ''
              ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION"
        );

        $parTable = [];
        foreach ($colonnes as $colonne) {
            $definition = sprintf('MODIFY `%s` DOUBLE %s', $colonne->column_name, $colonne->is_nullable === 'YES' ? 'NULL' : 'NOT NULL');

            // MariaDB rapporte l'absence de défaut par la chaîne 'NULL'
            if ($colonne->column_default !== null && strtoupper($colonne->column_default) !== 'NULL') {
                $definition .= ' DEFAULT ' . (float) trim($colonne->column_default, "'");
            }
            if ($colonne->column_comment !== '') {
                $definition .= ' COMMENT ' . DB::getPdo()->quote($colonne->column_comment);
            }

            $parTable[$colonne->table_name][] = $definition;
        }

        // Une seule reconstruction par table
        foreach ($parTable as $table => $definitions) {
            DB::statement(sprintf('ALTER TABLE `%s` %s', $table, implode(', ', $definitions)));
        }
    }

    public function down(): void
    {
        // Volontairement vide : revenir à DOUBLE(8,2) tronquerait les montants saisis depuis
    }
};
