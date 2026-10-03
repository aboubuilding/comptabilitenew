<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->string('reference', 50)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('magasin_id')->nullable()->after('type');
            $table->unsignedBigInteger('magasin_destination_id')->nullable()->after('magasin_id');
            $table->string('motif', 200)->nullable()->after('nom_magasinier');
            $table->text('commentaire')->nullable()->after('motif');
            $table->unsignedBigInteger('utilisateur_id')->nullable()->after('commentaire');
        });
    }

    public function down(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->dropColumn([
                'reference', 'magasin_id', 'magasin_destination_id',
                'motif', 'commentaire', 'utilisateur_id',
            ]);
        });
    }
};