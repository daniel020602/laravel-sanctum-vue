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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code')->unique(); 
            $table->string('email'); 
            $table->string('name'); 
            $table->string('phone')->unique(); 
            $table->date('date');
            $table->time('time'); 
            $table->boolean('is_confirmed')->default(false); // Confirmation status
            $table->foreignId('table_id')->constrained('tables'); // Foreign key to tables
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
