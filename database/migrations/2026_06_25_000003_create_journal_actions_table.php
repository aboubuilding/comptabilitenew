<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des actions sensibles (CDC 6.4) :
 * qui a fait quoi, quand, depuis quelle IP, sur quel module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nom')->nullable();        // snapshot au moment de l'action
            $table->string('user_login')->nullable();

            $table->string('module');                       // ex: Scolarite, Finances, Rh...
            $table->string('action');                       // ex: create, update, delete, login, export...
            $table->string('entite')->nullable();           // ex: Assure, Employe...
            $table->unsignedBigInteger('entite_id')->nullable();

            $table->text('description')->nullable();        // message lisible
            $table->json('payload')->nullable();            // données utiles (diff, filtres...)

            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('route')->nullable();
            $table->string('methode_http', 10)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index(['entite', 'entite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_actions');
    }
};