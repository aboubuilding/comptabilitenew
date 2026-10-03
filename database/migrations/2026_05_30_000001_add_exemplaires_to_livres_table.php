<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livres', function (Blueprint $table) {
            $table->integer('nombre_exemplaires')->default(1)->after('numero_isbn');
            $table->integer('nombre_disponibles')->default(1)->after('nombre_exemplaires');
            $table->text('description')->nullable()->after('nombre_disponibles');
            $table->string('photo')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('livres', function (Blueprint $table) {
            $table->dropColumn(['nombre_exemplaires', 'nombre_disponibles', 'description', 'photo']);
        });
    }
};