<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Login menyamakan username ke huruf kecil, jadi simpan dalam bentuk
        // yang sama agar "WarungBudi" dan "warungbudi" tidak jadi dua akun.
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9._]+$/',
                'unique:users,username',
            ],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'business_name' => 'nama usaha',
            'name' => 'nama pemilik',
            'username' => 'nama pengguna',
            'email' => 'email',
            'password' => 'kata sandi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Nama pengguna hanya boleh berisi huruf kecil, angka, titik, dan garis bawah.',
            'username.unique' => 'Nama pengguna ini sudah dipakai. Coba yang lain.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.confirmed' => 'Ulangi kata sandi belum sama.',
        ];
    }
}
