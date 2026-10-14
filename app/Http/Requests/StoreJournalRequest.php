<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'journal_date'     => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description'      => ['required', 'string', 'max:500'],
            'items'            => ['required', 'array', 'min:2'],
            'items.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'items.*.debit'      => ['nullable', 'numeric', 'min:0'],
            'items.*.credit'     => ['nullable', 'numeric', 'min:0'],
            'items.*.memo'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $totalDebit = collect($items)->sum('debit');
            $totalCredit = collect($items)->sum('credit');

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $validator->errors()->add('items', 'Total Debit (Rp ' . number_format($totalDebit, 0) . ') dan Total Kredit (Rp ' . number_format($totalCredit, 0) . ') tidak seimbang!');
            }

            if ($totalDebit <= 0) {
                $validator->errors()->add('items', 'Total nilai transaksi jurnal harus lebih besar dari 0.');
            }
        });
    }
}
