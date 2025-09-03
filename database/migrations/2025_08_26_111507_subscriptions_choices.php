<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->foreignId('week_menu_id')->constrained('week_menus')->onDelete('cascade');
            $table->tinyInteger('day')->unsigned();
            $table->timestamps();

            $table->unique(['subscription_id', 'week_menu_id']);
        });
        DB::statement("ALTER TABLE `subscription_choices` ADD CONSTRAINT `chk_subscription_choices_day_range` CHECK (`day` BETWEEN 1 AND 5)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_choices');
    }
};
