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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic link to Task or Event
            $table->morphs('billable'); 
            
            $table->text('description');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            
            // Financials
            $table->decimal('hours', 8, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('amount', 12, 2);
            
            // Link to invoice (nullable because it starts as unbilled)
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
