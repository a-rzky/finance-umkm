<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    /** Batas atas yang masuk akal untuk satu transaksi UMKM (1 triliun). */
    private const MAX_AMOUNT = 1_000_000_000_000;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:'.self::MAX_AMOUNT],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'nullable',
                // Kategori wajib milik tenant sendiri dan jenisnya harus cocok,
                // supaya ID kategori tenant lain tidak bisa disisipkan lewat request.
                Rule::exists('categories', 'id')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('type', $this->input('type')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis transaksi',
            'amount' => 'nominal',
            'occurred_on' => 'tanggal',
            'note' => 'keterangan',
            'category_id' => 'kategori',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Nominal harus lebih dari nol.',
            'occurred_on.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
            'category_id.exists' => 'Kategori tidak sesuai dengan jenis transaksi.',
        ];
    }
}
