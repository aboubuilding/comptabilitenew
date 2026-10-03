<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->boolean('capacite_limitee')->default(false)->after('capacite');
            $table->date('date_limite_confirmation')->nullable()->after('capacite_limitee');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn(['capacite_limitee', 'date_limite_confirmation']);
        });
    }
};