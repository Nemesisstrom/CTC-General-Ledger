<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->email)) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }

    public function rules(): array
    {
        Validator::extendImplicit(
            'forbidden_client_field',
            function (string $attribute, mixed $value, array $parameters, \Illuminate\Validation\Validator $validator): bool {
                return !array_key_exists($attribute, $validator->getData());
            },
            'Field tidak dapat ditentukan saat registrasi.'
        );

        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'branch_id'   => ['required', 'integer', 'exists:branches,id'],
            'password'    => ['required', 'confirmed', Password::defaults()],
            'role'        => ['forbidden_client_field'],
            'base_salary' => ['forbidden_client_field'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'                       => 'Alamat email ini sudah terdaftar. Silakan langsung login atau gunakan fitur lupa password.',
            'branch_id.required'                 => 'Silakan pilih cabang operasional terlebih dahulu.',
            'branch_id.exists'                   => 'Cabang operasional yang dipilih tidak valid.',
            'role.forbidden_client_field'        => 'Hak akses (role) tidak dapat ditentukan saat registrasi.',
            'base_salary.forbidden_client_field' => 'Gaji pokok tidak dapat ditentukan saat registrasi.',
        ];
    }
}
