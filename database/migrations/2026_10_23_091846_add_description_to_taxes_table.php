<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengecekan agar tidak error jika kolom 'description' sudah dibuat di migrasi utama
        if (!Schema::hasColumn('taxes', 'description')) {
            Schema::table('taxes', function (Blueprint $table) {
                $table->text('description')->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('taxes', 'description')) {
            Schema::table('taxes', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};