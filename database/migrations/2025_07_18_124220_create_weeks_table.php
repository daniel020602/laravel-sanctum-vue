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
        Schema::create('weeks', function (Blueprint $table) {
            $table->id();
            $table->integer('week');
            $table->integer('soup');
            $table->integer('day1a');
            $table->integer('day1b');
            $table->integer('day1c')->nullable();
            $table->integer('day2a');
            $table->integer('day2b');
            $table->integer('day2c')->nullable();
            $table->integer('day3a');
            $table->integer('day3b');
            $table->integer('day3c')->nullable();
            $table->integer('day4a');
            $table->integer('day4b');
            $table->integer('day4c')->nullable();
            $table->integer('day5a');
            $table->integer('day5b');
            $table->integer('day5c')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weeks');
    }
};
