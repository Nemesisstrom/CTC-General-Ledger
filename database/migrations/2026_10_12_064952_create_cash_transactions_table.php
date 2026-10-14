<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique()->comment('Contoh: TRX-IN-202610-001');
            $table->date('transaction_date');
            $table->enum('type', ['in', 'out'])->comment('in = Kas Masuk, out = Kas Keluar');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->comment('Akun Kas/Bank');
            $table->foreignId('opposite_account_id')->constrained('chart_of_accounts')->comment('Akun Lawan (Pendapatan/Beban)');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
