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
       Schema::table('abonnements_bus', function (Blueprint $table) {
    if (!Schema::hasColumn('abonnements_bus', 'zone_id')) {
        $table->foreignId('zone_id')->nullable()->after('inscription_id')->constrained('zones');
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
        Schema::table('abonnements_bus', function (Blueprint $table) {
            //
        });
    }
};
