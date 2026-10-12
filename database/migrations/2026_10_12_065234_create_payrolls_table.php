<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header Penggajian Karyawan
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('period', 7); // Format: 'YYYY-MM'
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('total_allowance', 15, 2)->default(0); // Tunjangan
            $table->decimal('total_deduction', 15, 2)->default(0); // Potongan Non-Pajak
            $table->decimal('pph21_amount', 15, 2)->default(0);   // Potongan PPh 21 Otomatis
            $table->decimal('net_salary', 15, 2);                 // Gaji Bersih Terima
            $table->foreignId('journal_id')->nullable()->constrained('journals')->comment('Link ke Jurnal Penggajian');
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Detail Rincian Komponen Gaji (Tunjangan, Bonus, BPJS, dll)
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['allowance', 'deduction']);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
