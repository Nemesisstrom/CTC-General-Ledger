<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        ChartOfAccount::query()->delete();

        // ==========================================
        // 1. KELOMPOK ASET (ASSETS - Normal Balance: Debit)
        // ==========================================
        $currentAsset = ChartOfAccount::create([
            'code' => '1-1000',
            'name' => 'Aset Lancar',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => null,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-1101',
            'name' => 'Kas Utama',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $currentAsset->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-1102',
            'name' => 'Bank BCA Operasional',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $currentAsset->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-1200',
            'name' => 'Piutang Usaha',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $currentAsset->id,
            'is_active' => true,
        ]);

        $fixedAsset = ChartOfAccount::create([
            'code' => '1-2000',
            'name' => 'Aset Tetap',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => null,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-2100',
            'name' => 'Peralatan Kantor',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $fixedAsset->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-2150',
            'name' => 'Akumulasi Penyusutan Peralatan',
            'type' => 'asset',
            'normal_balance' => 'credit',
            'parent_id' => $fixedAsset->id,
            'is_active' => true,
        ]);

        // ==========================================
        // 2. KELOMPOK KEWAJIBAN / LIABILITAS (LIABILITIES)
        // ==========================================
        $currentLiability = ChartOfAccount::create([
            'code' => '2-1000',
            'name' => 'Liabilitas Jangka Pendek',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'parent_id' => null,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '2-1100',
            'name' => 'Utang Usaha',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'parent_id' => $currentLiability->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '2-1200',
            'name' => 'Utang Gaji',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'parent_id' => $currentLiability->id,
            'is_active' => true,
        ]);

        // ==========================================
        // 3. KELOMPOK EKUITAS / MODAL (EQUITY)
        // ==========================================
        $equityParent = ChartOfAccount::create([
            'code' => '3-1000',
            'name' => 'Ekuitas Pemilik',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'parent_id' => null,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '3-1100',
            'name' => 'Modal Disetor',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'parent_id' => $equityParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '3-1200',
            'name' => 'Laba Ditahan',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'parent_id' => $equityParent->id,
            'is_active' => true,
        ]);

        // ==========================================
        // 4. KELOMPOK PENDAPATAN (REVENUE)
        // ==========================================
        $revenueParent = ChartOfAccount::create([
            'code' => '4-1000',
            'name' => 'Pendapatan Usaha',
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'parent_id' => null,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '4-1100',
            'name' => 'Pendapatan Jasa Layanan Utama',
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'parent_id' => $revenueParent->id,
            'is_active' => true,
        ]);

        // ==========================================
        // 5. KELOMPOK BEBAN (EXPENSE)
        // ==========================================
        $expenseParent = ChartOfAccount::create([
            'code' => '5-1000',
            'name' => 'Beban Operasional',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => null,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1101',
            'name' => 'Beban Gaji & Honorarium Tim',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1102',
            'name' => 'Beban Listrik, Air, & Internet',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1103',
            'name' => 'Beban Penyusutan Peralatan',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1104',
            'name' => 'Beban Hosting & Domain',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1105',
            'name' => 'Beban Perlengkapan Kantor (ATK)',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1106',
            'name' => 'Beban Transportasi & Perjalanan',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1107',
            'name' => 'Beban Konsumsi & Jamuan',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id,
            'is_active' => true,
        ]);
    }
}
