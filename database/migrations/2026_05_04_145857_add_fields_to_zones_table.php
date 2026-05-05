<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            if (!Schema::hasColumn('zones', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('zones', 'tarif_base')) {
                $table->decimal('tarif_base', 10, 2)->default(0)->after('description');
            }
            if (!Schema::hasColumn('zones', 'ordre')) {
                $table->integer('ordre')->default(0)->after('tarif_base');
            }
            if (!Schema::hasColumn('zones', 'couleur')) {
                $table->string('couleur')->nullable()->after('ordre');
            }
            
        });
    }

    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['code', 'tarif_base', 'ordre', 'couleur']);
        });
    }
};