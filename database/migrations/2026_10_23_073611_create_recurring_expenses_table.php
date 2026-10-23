<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Listrik Kantor, Internet IndiHome, Air PDAM
            $table->text('description')->nullable();
            $table->foreignId('expense_account_id')->constrained('chart_of_accounts')->onDelete('cascade'); // Rekening Beban
            $table->foreignId('cash_account_id')->constrained('chart_of_accounts')->onDelete('cascade');    // Rekening Kas/Utang
            $table->decimal('amount', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
