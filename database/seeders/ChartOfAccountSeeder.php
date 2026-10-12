<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama agar tidak duplikat saat seeding ulang
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
            'parent_id' => $currentAsset->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-1102',
            'name' => 'Bank BCA Operasional',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $currentAsset->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-1200',
            'name' => 'Piutang Usaha',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $currentAsset->id, // Akun Turunan
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
            'parent_id' => $fixedAsset->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '1-2150',
            'name' => 'Akumulasi Penyusutan Peralatan',
            'type' => 'asset',
            'normal_balance' => 'credit', // Kontra aset
            'parent_id' => $fixedAsset->id, // Akun Turunan
            'is_active' => true,
        ]);


        // ==========================================
        // 2. KELOMPOK KEWAJIBAN / LIABILITAS (LIABILITIES - Normal Balance: Credit)
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
            'parent_id' => $currentLiability->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '2-1200',
            'name' => 'Utang Gaji',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'parent_id' => $currentLiability->id, // Akun Turunan
            'is_active' => true,
        ]);


        // ==========================================
        // 3. KELOMPOK EKUITAS / MODAL (EQUITY - Normal Balance: Credit)
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
            'parent_id' => $equityParent->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '3-1200',
            'name' => 'Laba Ditahan',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'parent_id' => $equityParent->id, // Akun Turunan
            'is_active' => true,
        ]);


        // ==========================================
        // 4. KELOMPOK PENDAPATAN (REVENUE - Normal Balance: Credit)
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
            'parent_id' => $revenueParent->id, // Akun Turunan
            'is_active' => true,
        ]);


        // ==========================================
        // 5. KELOMPOK BEBAN (EXPENSE - Normal Balance: Debit)
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
            'name' => 'Beban Gaji Karyawan',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1102',
            'name' => 'Beban Listrik, Air, & Internet',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id, // Akun Turunan
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '5-1103',
            'name' => 'Beban Penyusutan Peralatan',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'parent_id' => $expenseParent->id, // Akun Turunan
            'is_active' => true,
        ]);
    }
}
