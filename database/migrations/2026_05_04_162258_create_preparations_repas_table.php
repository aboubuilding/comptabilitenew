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
       Schema::create('preparations_repas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
    $table->date('date_preparation');
    $table->integer('nombre_parts');
    $table->decimal('cout_reel', 12, 2)->nullable();
    $table->text('observations')->nullable();
    $table->foreignId('responsable_id')->nullable()->constrained('users');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preparations_repas');
    }
};
