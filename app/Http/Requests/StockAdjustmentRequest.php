<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StockAdjustmentRequest extends FormRequest
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
        $direction = (string) $this->route('direction');

        return [
            'adjustment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'lines.*.warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'lines.*.location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'lines.*.qty' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999999999'],
            'lines.*.unit_cost' => [
                Rule::requiredIf($direction === 'in'),
                'nullable',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $seen = [];

                foreach ($this->input('lines', []) as $index => $line) {
                    if (! is_array($line)) {
                        continue;
                    }

                    $warehouseId = $line['warehouse_id'] ?? null;
                    $locationId = $line['location_id'] ?? null;

                    if ($locationId && $warehouseId && ! Location::query()
                        ->whereKey($locationId)
                        ->where('warehouse_id', $warehouseId)
                        ->exists()) {
                        $validator->errors()->add(
                            "lines.{$index}.location_id",
                            'Lokasi harus berada di gudang pada baris ini.',
                        );
                    }

                    if (empty($line['item_id']) || empty($warehouseId)) {
                        continue;
                    }

                    $key = $line['item_id'].'|'.$warehouseId.'|'.($locationId ?? 'null');
                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "lines.{$index}.item_id",
                            'Barang, gudang, dan lokasi yang sama tidak boleh dicatat lebih dari sekali.',
                        );
                    }
                    $seen[$key] = true;
                }
            },
        ];
    }
}
