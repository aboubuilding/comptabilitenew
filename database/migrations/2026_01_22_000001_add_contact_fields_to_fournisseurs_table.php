<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('email_contact')->nullable()->after('telephone_contact');
            $table->string('nif', 50)->nullable()->after('adresse');   // Numéro d'identification fiscale
            $table->string('rccm', 50)->nullable()->after('nif');      // Registre du commerce
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn(['email_contact', 'nif', 'rccm']);
        });
    }
};