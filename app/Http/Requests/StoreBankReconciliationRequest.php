<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id'        => ['required', 'exists:chart_of_accounts,id'],
            'statement_date'    => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ];
    }
}
