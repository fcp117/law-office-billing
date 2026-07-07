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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_person_email')->nullable();
            $table->date('start_date');
            $table->string('partner_in_charge');
            $table->boolean('with_retainer')->default(false);
            $table->date('retainer_date')->nullable();
            $table->decimal('retainer_amount', 12, 2)->nullable();
            $table->date('retainer_amount_set_date')->nullable();
            $table->timestamps(); // Handles created_at and updated_at automatically
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
