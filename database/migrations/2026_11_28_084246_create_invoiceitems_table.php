<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('matter_id')->nullable()->change();
            $table->foreignId('client_id')->nullable()->after('matter_id')->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('PHP')->after('invoice_number');
            $table->string('billing_source', 32)->default('matter')->after('currency'); // matter, special_billing
            $table->string('period_key', 20)->nullable()->after('billing_source');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recurring_arrangement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('beneficiary_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period_key', 20)->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0.00);
            $table->timestamp('beneficiary_remitted_at')->nullable();
            $table->timestamps();

            $table->unique(['recurring_arrangement_id', 'period_key'], 'uniq_arrangement_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['currency', 'billing_source', 'period_key']);
        });
    }
};