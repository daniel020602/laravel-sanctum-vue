<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('week_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('week_id')->constrained('weeks')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week');
            $table->enum('option', ['a', 'b', 'c','soup'])->default('a');
            $table->timestamps();

            $table->unique(['week_id', 'day_of_week', 'option']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('week_menus');
    }
};
