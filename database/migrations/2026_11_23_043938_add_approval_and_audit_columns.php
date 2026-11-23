<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'cash_transactions',
            'payrolls',
            'journals',
            'bank_reconciliations',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'approval_status')) {
                    $table->string('approval_status', 20)->default('draft')->index();
                }
                if (!Schema::hasColumn($tableName, 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'cash_transactions',
            'payrolls',
            'journals',
            'bank_reconciliations',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach (['approval_status', 'created_by', 'approved_by', 'approved_at', 'deleted_by'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
