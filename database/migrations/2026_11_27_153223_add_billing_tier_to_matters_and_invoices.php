<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->string('billing_tier', 32)->default('standard')->after('billing_type');
            $table->decimal('billing_percentage', 5, 2)->default(100.00)->after('billing_tier');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('billing_tier', 32)->default('standard')->after('invoice_number');
            $table->decimal('billing_percentage', 5, 2)->default(100.00)->after('billing_tier');
        });
    }

    public function down(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->dropColumn(['billing_tier', 'billing_percentage']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['billing_tier', 'billing_percentage']);
        });
    }
};