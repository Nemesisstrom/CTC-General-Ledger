<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2); // Saldo Rekening Koran
            $table->decimal('book_balance', 15, 2);      // Saldo Pembukuan
            $table->decimal('difference', 15, 2)->default(0);
            $table->enum('status', ['pending', 'reconciled'])->default('pending');
            $table->foreignId('reconciled_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
