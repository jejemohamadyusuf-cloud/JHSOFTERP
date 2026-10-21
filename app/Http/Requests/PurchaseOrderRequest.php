<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\MasterPriceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'lines.*.qty' => ['required', 'numeric', 'decimal:0,4', 'gt:0', 'max:99999999999999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('order_date')) {
                    return;
                }

                $prices = app(MasterPriceService::class);

                foreach ($this->input('lines', []) as $index => $line) {
                    if (! is_array($line) || empty($line['item_id'])) {
                        continue;
                    }

                    if ($prices->priceForDate((int) $line['item_id'], (string) $this->input('order_date'), type: 'buy') === null) {
                        $validator->errors()->add(
                            "lines.{$index}.item_id",
                            'Master Harga belum memiliki harga beli yang berlaku pada tanggal PO ini.',
                        );
                    }
                }
            },
        ];
    }
}
