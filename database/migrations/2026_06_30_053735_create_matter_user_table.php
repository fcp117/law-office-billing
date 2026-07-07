<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matter_user', function (Blueprint $table) {
            $table->id();
            
            // The Foreign Keys
            $table->foreignId('matter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // The Extra Pivot Data
            $table->string('assignment_role'); // e.g., 'Supervising Partner', 'Handling Lawyer'
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matter_user');
    }
};