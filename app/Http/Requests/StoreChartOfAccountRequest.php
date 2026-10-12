<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coaId = $this->route('chart_of_account') ? $this->route('chart_of_account')->id : null;

        return [
            'code'           => ['required', 'string', 'max:20', Rule::unique('chart_of_accounts', 'code')->ignore($coaId)],
            'name'           => ['required', 'string', 'max:255'],
            'type'           => ['required', 'in:asset,liability,equity,revenue,expense'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'parent_id'      => ['nullable', 'exists:chart_of_accounts,id'],
            'is_active'      => ['boolean'],
        ];
    }
}
