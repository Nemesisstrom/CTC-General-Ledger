<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read int|null $branch_id
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'branch_id'   => ['nullable', 'exists:branches,id'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            // Mencegah client menyuntikkan role atau base_salary saat registrasi
            'role'        => ['prohibited'],
            'base_salary' => ['missing'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Nama lengkap wajib diisi.',
            'email.required'      => 'Email wajib diisi.',
            'email.unique'        => 'Email sudah terdaftar.',
            'password.required'   => 'Password wajib diisi.',
            'password.min'        => 'Password minimal 8 karakter.',
            'password.confirmed'  => 'Konfirmasi password tidak cocok.',
            'role.prohibited'     => 'Role pengguna ditentukan oleh sistem.',
            'base_salary.missing' => 'Gaji pokok ditentukan oleh sistem.',
        ];
    }
}
