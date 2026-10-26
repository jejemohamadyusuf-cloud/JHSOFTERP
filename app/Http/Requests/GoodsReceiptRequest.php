<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class GoodsReceiptRequest extends FormRequest
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
            'purchase_order_id' => ['required', 'integer', Rule::exists('purchase_orders', 'id')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'receipt_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', 'distinct', Rule::exists('purchase_order_lines', 'id')],
            'lines.*.qty' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999999999'],
            'lines.*.location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $warehouseId = $this->input('warehouse_id');

                if (! $warehouseId) {
                    return;
                }

                foreach ($this->input('lines', []) as $index => $line) {
                    if (! is_array($line)) {
                        continue;
                    }

                    if (empty($line['location_id'])) {
                        continue;
                    }

                    if (! Location::query()
                        ->whereKey($line['location_id'])
                        ->where('warehouse_id', $warehouseId)
                        ->exists()) {
                        $validator->errors()->add(
                            "lines.{$index}.location_id",
                            'Lokasi harus berada di gudang penerimaan yang dipilih.',
                        );
                    }
                }
            },
        ];
    }
}
