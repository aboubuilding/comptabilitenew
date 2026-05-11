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
            if (!Schema::hasColumn('details', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('produit_id')->constrained('services');
            }
            if (!Schema::hasColumn('details', 'activite_id')) {
                $table->foreignId('activite_id')->nullable()->after('service_id')->constrained('activites');
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
