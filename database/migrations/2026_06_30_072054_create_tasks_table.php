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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable(); // <-- Add this line
            $table->dateTime('deadline')->nullable();
            $table->string('status')->default('Pending');

            $table->string('billing_type')->default('hourly');
            $table->decimal('fixed_fee_amount', 12, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
