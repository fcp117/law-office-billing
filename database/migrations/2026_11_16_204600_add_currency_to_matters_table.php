<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('matters', 'currency')) {
            Schema::table('matters', function (Blueprint $table) {
                $table->string('currency', 3)->default('PHP')->after('billing_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('matters', 'currency')) {
            Schema::table('matters', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};