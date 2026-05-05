<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('details', function (Blueprint $table) {
    if (!Schema::hasColumn('details', 'inscription_cantine_id')) {
        $table->foreignId('inscription_cantine_id')->nullable()
              ->after('abonnement_bus_id')
              ->constrained('inscriptions_cantine')->nullOnDelete();
    }
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('details', function (Blueprint $table) {
            //
        });
    }
};
