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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending','delivery','prepared', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10, 2); // Total amount for the order
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        // Note: If you want to keep the order history, you might not want to drop this table.
        // Instead, consider implementing a soft delete or archiving strategy.
    }
};
