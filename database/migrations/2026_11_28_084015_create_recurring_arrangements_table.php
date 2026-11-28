<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_arrangements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32); // retainer, secondment, directors_fee
            $table->foreignId('beneficiary_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PHP');
            $table->boolean('is_vatable')->default(true);
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, quarterly, semi_annual, annual
            $table->unsignedTinyInteger('billing_day')->default(1); // 1 to 28
            $table->string('grouping_mode', 20)->default('individual'); // grouped, individual
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('Active'); // Active, Paused, Ended
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'billing_cycle']);
            $table->index(['client_id', 'type', 'grouping_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_arrangements');
    }
};