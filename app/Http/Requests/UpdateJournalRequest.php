<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $journalId = $this->route('journal') ? $this->route('journal')->id : $this->input('id');

        return [
            'transaction_date' => ['required', 'date'],
            'reference_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('journals', 'reference_number')->ignore($journalId),
            ],
            'description'      => ['nullable', 'string', 'max:255'],
            'items'            => ['required', 'array', 'min:2'],
            'items.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'items.*.debit'    => ['required', 'numeric', 'min:0'],
            'items.*.credit'   => ['required', 'numeric', 'min:0'],
        ];
    }

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
}