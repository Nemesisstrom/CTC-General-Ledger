<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 64)->unique(); // contoh: cash_transactions, payrolls, journals, bank_reconciliations
            $table->string('label', 120);
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_settings');
    }
};
