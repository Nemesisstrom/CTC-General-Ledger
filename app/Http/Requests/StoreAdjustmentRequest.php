<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_date'        => ['required', 'date'],
            'description'            => ['required', 'string', 'max:500'],
            'expense_account_id'     => ['required', 'exists:chart_of_accounts,id'], // Akun Beban (Debit)
            'accumulated_account_id' => ['required', 'exists:chart_of_accounts,id', 'different:expense_account_id'], // Akun Akumulasi / Kewajiban (Kredit)
            'amount'                 => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'accumulated_account_id.different' => 'Akun Kredit tidak boleh sama dengan Akun Debit.',
            'amount.min'                        => 'Nilai penyesuaian minimal Rp 1.',
        ];
    }
}
