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
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['required', 'string', 'max:50', 'unique:journals,reference_number'],
            'description'      => ['nullable', 'string', 'max:255'],
            'items'            => ['required', 'array', 'min:2'],
            'items.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'items.*.debit'    => ['required', 'numeric', 'min:0'],
            'items.*.credit'   => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Validasi kustom untuk memastikan Total Debet = Total Kredit.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $totalDebit = collect($items)->sum('debit');
            $totalCredit = collect($items)->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.0001) {
                $validator->errors()->add(
                    'items',
                    'Total Debet (' . number_format($totalDebit, 2) . ') harus sama dengan Total Kredit (' . number_format($totalCredit, 2) . ').'
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
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'reference_number.required' => 'Nomor referensi wajib diisi.',
            'reference_number.unique'   => 'Nomor referensi sudah digunakan.',
            'items.required'           => 'Rincian jurnal wajib diisi.',
            'items.min'                => 'Jurnal minimal harus memiliki 2 entri (Debet dan Kredit).',
        ];
    }
}