<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MasterPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class MasterPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['price_type' => $this->input('price_type', 'sell')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'price_type' => ['required', 'in:sell,buy'],
            'price' => ['required', 'numeric', 'decimal:0,4', 'min:0', 'max:99999999999999'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('end_date') !== null && $this->input('end_date') !== '') {
                    return;
                }

                $nextPriceExists = MasterPrice::query()
                    ->where('item_id', $this->integer('item_id'))
                    ->where('price_type', $this->input('price_type'))
                    ->when(
                        $this->route('master_price'),
                        fn ($query, $id) => $query->whereKeyNot((int) $id),
                    )
                    ->whereDate('start_date', '>', $this->input('start_date'))
                    ->exists();

                if ($nextPriceExists) {
                    $validator->errors()->add(
                        'end_date',
                        'Tanggal akhir wajib diisi sebelum tanggal mulai harga berikutnya.',
                    );
                }
            },
        ];
    }
}
