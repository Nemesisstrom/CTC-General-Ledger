<?php

namespace Database\Seeders;

use App\Models\ApprovalSetting;
use Illuminate\Database\Seeder;

class ApprovalSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'module_key' => 'cash_transactions',
                'label'      => 'Transaksi Kas & Bank',
                'min_amount' => 5000000.00,
                'is_active'  => true,
            ],
            [
                'module_key' => 'payrolls',
                'label'      => 'Penggajian (Payroll)',
                'min_amount' => 0.00,
                'is_active'  => true,
            ],
            [
                'module_key' => 'journals',
                'label'      => 'Jurnal Umum & Penyesuaian',
                'min_amount' => 10000000.00,
                'is_active'  => true,
            ],
            [
                'module_key' => 'bank_reconciliations',
                'label'      => 'Rekonsiliasi Bank',
                'min_amount' => 0.00,
                'is_active'  => true,
            ],
        ];

        foreach ($defaults as $item) {
            ApprovalSetting::query()->updateOrCreate(
                ['module_key' => $item['module_key']],
                [
                    'label'     => $item['label'],
                    'min_amount' => $item['min_amount'],
                    'is_active' => $item['is_active'],
                ]
            );

            ApprovalSetting::clearModuleCache($item['module_key']);
        }
    }
}
