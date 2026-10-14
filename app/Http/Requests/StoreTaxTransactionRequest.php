<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date'   => ['required', 'date'],
            'tax_id'             => ['required', 'exists:taxes,id'],
            'transaction_type'   => ['required', 'in:sales,purchase'], // Penjualan (PPN Keluaran) / Pembelian (PPN Masukan / PPh Cut)
            'subtotal'           => ['required', 'numeric', 'min:1'],
            'cash_account_id'    => ['required', 'exists:chart_of_accounts,id'],
            'revenue_expense_id' => ['required', 'exists:chart_of_accounts,id'], // Akun Pendapatan atau Beban
            'description'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
