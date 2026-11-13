<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->index(['journal_date', 'status'], 'idx_journals_date_status');
            $table->index('reference_number', 'idx_journals_reference');
        });

        Schema::table('journal_details', function (Blueprint $table) {
            $table->index(['account_id', 'journal_id'], 'idx_jdetails_account_journal');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->index(['transaction_date', 'type'], 'idx_cash_trx_date_type');
            $table->index('account_id', 'idx_cash_trx_account');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->index(['type', 'is_active'], 'idx_coa_type_active');
            $table->index('parent_id', 'idx_coa_parent');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('idx_journals_date_status');
            $table->dropIndex('idx_journals_reference');
        });

        Schema::table('journal_details', function (Blueprint $table) {
            $table->dropIndex('idx_jdetails_account_journal');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cash_trx_date_type');
            $table->dropIndex('idx_cash_trx_account');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_coa_type_active');
            $table->dropIndex('idx_coa_parent');
        });
    }
};
