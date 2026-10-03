<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes additives sur users :
 *  - role_id      : lien vers le référentiel roles (remplace role tinyInteger)
 *  - remember_token : pour l'auth Laravel standard
 *  - last_login_at / last_login_ip : traçabilité (CDC 6.4)
 *  - deleted_at   : soft delete
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('role')
                      ->constrained('roles')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable();
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['role_id', 'remember_token', 'last_login_at', 'last_login_ip', 'deleted_at'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    if ($col === 'role_id') {
                        $table->dropForeign(['role_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};