<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prets', function (Blueprint $table) {
            $table->decimal('caution', 10, 2)->nullable()->after('statut_pret');
            $table->text('observations')->nullable()->after('caution');
            $table->unsignedBigInteger('utilisateur_id')->nullable()->after('observations');
            $table->date('date_retard_constate')->nullable()->after('utilisateur_id');

            $table->foreign('utilisateur_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prets', function (Blueprint $table) {
            $table->dropForeign(['utilisateur_id']);
            $table->dropColumn(['caution', 'observations', 'utilisateur_id', 'date_retard_constate']);
        });
    }
};