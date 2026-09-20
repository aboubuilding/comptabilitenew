<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->float('montant')->nullable()->after('emetteur');
            $table->date('date_rapprochement')->nullable()->after('date_encaissement');
            $table->unsignedBigInteger('rapproche_par')->nullable()->after('date_rapprochement');
            $table->string('motif_rejet')->nullable()->after('rapproche_par');
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropColumn(['montant', 'date_rapprochement', 'rapproche_par', 'motif_rejet']);
        });
    }
};