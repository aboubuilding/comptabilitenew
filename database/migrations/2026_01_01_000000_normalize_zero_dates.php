<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La base historique (MariaDB non strict) contient des dates '0000-00-00'.
     * En mode strict, MySQL refuse toute reconstruction de table (ALTER, clé
     * étrangère...) tant qu'elles existent : on les ramène à NULL.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $colonnes = DB::select(
            "SELECT c.TABLE_NAME AS table_name, c.COLUMN_NAME AS column_name
               FROM information_schema.COLUMNS c
               JOIN information_schema.TABLES t
                 ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
              WHERE c.TABLE_SCHEMA = DATABASE()
                AND t.TABLE_TYPE = 'BASE TABLE'
                AND c.DATA_TYPE IN ('date', 'datetime', 'timestamp')
                AND c.IS_NULLABLE = 'YES'"
        );

        $modeInitial = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = ''");

        try {
            foreach ($colonnes as $colonne) {
                DB::statement(sprintf(
                    "UPDATE `%s` SET `%s` = NULL WHERE CAST(`%s` AS CHAR) LIKE '0000-00-00%%'",
                    $colonne->table_name,
                    $colonne->column_name,
                    $colonne->column_name
                ));
            }
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$modeInitial]);
        }
    }

    public function down(): void
    {
        // Irréversible : les dates nulles ne sont pas restaurées en '0000-00-00'
    }
};
