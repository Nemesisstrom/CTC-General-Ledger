<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header Jurnal
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique(); // JRN-20260101-0001
            $table->date('journal_date');
            $table->string('reference_number')->nullable();
            $table->text('description');
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Detail Entri Jurnal (Double-Entry Ledger)
        Schema::create('journal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->comment('Jika ada pajak terkait');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_details');
        Schema::dropIfExists('journals');
    }
};
