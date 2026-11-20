<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name');
                $table->string('phone', 25)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $tables = ['users', 'journals', 'cash_transactions', 'bank_reconciliations', 'payrolls'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $onDelete = $tableName === 'users' ? 'nullOnDelete' : 'cascadeOnDelete';
                    $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->{$onDelete}();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['payrolls', 'bank_reconciliations', 'cash_transactions', 'journals', 'users'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }

        Schema::dropIfExists('branches');
    }
};
