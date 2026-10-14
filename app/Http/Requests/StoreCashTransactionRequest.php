<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date'    => ['required', 'date'],
            'type'                => ['required', 'in:in,out'],
            'account_id'          => ['required', 'exists:chart_of_accounts,id'],
            'opposite_account_id' => ['required', 'exists:chart_of_accounts,id', 'different:account_id'],
            'amount'              => ['required', 'numeric', 'min:1'],
            'description'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'opposite_account_id.different' => 'Akun lawan tidak boleh sama dengan akun kas/bank.',
            'amount.min'                    => 'Jumlah transaksi minimal Rp 1.',
        ];
    }
}
