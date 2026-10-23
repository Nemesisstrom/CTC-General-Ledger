<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tax;
use App\Models\ChartOfAccount;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil ID rekening penampung utang/piutang pajak
        $taxAccount = ChartOfAccount::where('code', 'LIKE', '2-%')
            ->orWhere('name', 'LIKE', '%Pajak%')
            ->first() ?? ChartOfAccount::first();

        if (!$taxAccount) {
            $this->command->error('ChartOfAccount kosong! Jalankan ChartOfAccountSeeder terlebih dahulu.');
            return;
        }

        $taxes = [
            [
                'code'       => 'PPN11',
                'name'       => 'PPN 11% (Pajak Pertambahan Nilai)',
                'rate'       => 11.00,
                'type'       => 'ppn', // Disesuaikan dari 'inclusive' ke 'ppn'
                'account_id' => $taxAccount->id,
                'is_active'  => true,
            ],
            [
                'code'       => 'PPH23',
                'name'       => 'PPh Pasal 23 (Potongan Jasa 2%)',
                'rate'       => 2.00,
                'type'       => 'pph23', // Disesuaikan dari 'withholding' ke 'pph23' atau 'pph'
                'account_id' => $taxAccount->id,
                'is_active'  => true,
            ],
            [
                'code'       => 'PPH21',
                'name'       => 'PPh Pasal 21 (Potongan Perorangan)',
                'rate'       => 5.00,
                'type'       => 'pph21', // Disesuaikan dari 'withholding' ke 'pph21' atau 'pph'
                'account_id' => $taxAccount->id,
                'is_active'  => true,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::updateOrCreate(['code' => $tax['code']], $tax);
        }
    }
}
