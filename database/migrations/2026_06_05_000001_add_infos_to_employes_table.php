<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->date('date_embauche')->nullable()->after('telephone');
            $table->date('date_naissance')->nullable()->after('date_embauche');
            $table->string('lieu_naissance', 150)->nullable()->after('date_naissance');
            $table->string('email', 150)->nullable()->after('lieu_naissance');
            $table->string('genre', 10)->nullable()->after('email');        // M / F
            $table->string('situation_familiale', 30)->nullable()->after('genre'); // celibataire, marie(e), divorce(e), veuf(ve)
            $table->date('date_fin_contrat')->nullable()->after('situation_familiale');
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropColumn([
                'date_embauche', 'date_naissance', 'lieu_naissance',
                'email', 'genre', 'situation_familiale', 'date_fin_contrat',
            ]);
        });
    }
};