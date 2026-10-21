<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi yang berlaku untuk permintaan ini.
     */
    public function rules(): array
    {
        return [
            // Mendukung field journal_date dari Blade maupun transaction_date
            'journal_date'     => ['required_without:transaction_date', 'nullable', 'date'],
            'transaction_date' => ['required_without:journal_date', 'nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'description'      => ['required', 'string', 'max:255'],
            'items'            => ['required', 'array', 'min:2'],

            // Mendukung baik account_id maupun chart_of_account_id
            'items.*.account_id'          => ['required_without:items.*.chart_of_account_id', 'nullable', 'exists:chart_of_accounts,id'],
            'items.*.chart_of_account_id' => ['required_without:items.*.account_id', 'nullable', 'exists:chart_of_accounts,id'],
            'items.*.debit'               => ['required', 'numeric', 'min:0'],
            'items.*.credit'              => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Validasi kustom untuk memastikan Total Debet = Total Kredit.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $totalDebit = collect($items)->sum(fn($i) => (float)($i['debit'] ?? 0));
            $totalCredit = collect($items)->sum(fn($i) => (float)($i['credit'] ?? 0));

            // Pastikan entri terisi nominal > 0
            if ($totalDebit <= 0 && $totalCredit <= 0) {
                $validator->errors()->add('items', 'Nominal transaksi jurnal harus lebih dari 0.');
                return;
            }

            // Keseimbangan Double-Entry Check
            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add(
                    'items',
                    'Total Debet (Rp ' . number_format($totalDebit, 0, ',', '.') . ') harus seimbang dengan Total Kredit (Rp ' . number_format($totalCredit, 0, ',', '.') . ').'
                );
            }
        });
    }

    /**
     * Pesan kustom validasi.
     */
    public function messages(): array
    {
        return [
            'journal_date.required_without' => 'Tanggal jurnal wajib diisi.',
            'transaction_date.required_without' => 'Tanggal jurnal wajib diisi.',
            'description.required'          => 'Keterangan jurnal wajib diisi.',
            'items.required'                => 'Rincian jurnal wajib diisi.',
            'items.min'                     => 'Jurnal minimal harus memiliki 2 entri (Debet dan Kredit).',
            'items.*.account_id.required_without' => 'Silakan pilih akun COA untuk setiap baris jurnal.',
        ];
    }
}
