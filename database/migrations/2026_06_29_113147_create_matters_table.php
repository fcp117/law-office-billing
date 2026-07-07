<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matters', function (Blueprint $table) {
            $table->id();
            
            // Core Matter Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('practice_area')->nullable();
            $table->string('type')->nullable();
            
            // Relational & Jurisdictional Details
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('authority')->nullable();
            
            // Party Information
            $table->string('party_represented')->nullable();
            $table->string('party_represented_role')->nullable();
            $table->string('party_adverse')->nullable();
            $table->string('party_adverse_role')->nullable();
            
            // Timeline & Status
            $table->string('status')->default('Active');
            $table->date('start_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->text('remarks')->nullable();
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matters');
    }
};