<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    /** Batas rentang agar satu permintaan tidak menarik data bertahun-tahun. */
    private const MAX_RANGE_DAYS = 366;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from') ?: today()->subDays(6)->toDateString(),
            'until' => $this->input('until') ?: today()->toDateString(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'until' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
                'before_or_equal:'.today()->addDay()->toDateString(),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $days = $this->date('from')->diffInDays($this->date('until'));

                if ($days > self::MAX_RANGE_DAYS) {
                    $validator->errors()->add('until', 'Rentang tanggal maksimal 1 tahun.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from' => 'tanggal mulai',
            'until' => 'tanggal akhir',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'until.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
        ];
    }
}
