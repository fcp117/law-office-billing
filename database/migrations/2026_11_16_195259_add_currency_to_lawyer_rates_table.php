<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lawyer_rates', 'currency')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->string('currency', 3)->default('USD')->after('rank');
            });
        }

        // Ensure user_id still has a supporting index for its foreign key before dropping the old composite unique index
        if (!Schema::hasIndex('lawyer_rates', 'lawyer_rates_user_id_index')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->index('user_id', 'lawyer_rates_user_id_index');
            });
        }

        if (Schema::hasIndex('lawyer_rates', ['user_id', 'effective_from'], 'unique')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'effective_from']);
            });
        }

        if (Schema::hasIndex('lawyer_rates', ['user_id', 'effective_from'], 'index')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'effective_from']);
            });
        }

        if (!Schema::hasIndex('lawyer_rates', 'lawyer_rates_user_currency_date_unique')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->unique(['user_id', 'currency', 'effective_from'], 'lawyer_rates_user_currency_date_unique');
            });
        }

        if (!Schema::hasIndex('lawyer_rates', 'lawyer_rates_user_currency_date_index')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->index(['user_id', 'currency', 'effective_from'], 'lawyer_rates_user_currency_date_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('lawyer_rates', 'lawyer_rates_user_currency_date_unique')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->dropUnique('lawyer_rates_user_currency_date_unique');
            });
        }

        if (Schema::hasIndex('lawyer_rates', 'lawyer_rates_user_currency_date_index')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->dropIndex('lawyer_rates_user_currency_date_index');
            });
        }

        if (!Schema::hasIndex('lawyer_rates', ['user_id', 'effective_from'], 'unique')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->unique(['user_id', 'effective_from']);
            });
        }

        if (!Schema::hasIndex('lawyer_rates', ['user_id', 'effective_from'], 'index')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->index(['user_id', 'effective_from']);
            });
        }

        if (Schema::hasIndex('lawyer_rates', 'lawyer_rates_user_id_index')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->dropIndex('lawyer_rates_user_id_index');
            });
        }

        if (Schema::hasColumn('lawyer_rates', 'currency')) {
            Schema::table('lawyer_rates', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};