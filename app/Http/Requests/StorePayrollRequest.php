<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'                   => ['required', 'exists:users,id'],
            'period'                    => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'allowances'                => ['nullable', 'array'],
            'allowances.*.name'         => ['required_with:allowances', 'string'],
            'allowances.*.amount'       => ['required_with:allowances', 'numeric', 'min:0'],
            'deductions'                => ['nullable', 'array'],
            'deductions.*.name'         => ['required_with:deductions', 'string'],
            'deductions.*.amount'       => ['required_with:deductions', 'numeric', 'min:0'],
            'salary_expense_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'cash_account_id'           => ['required', 'exists:chart_of_accounts,id'],
            'pph_account_id'            => ['nullable', 'exists:chart_of_accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.regex' => 'Format periode harus YYYY-MM (Contoh: 2026-10).',
        ];
    }
}
