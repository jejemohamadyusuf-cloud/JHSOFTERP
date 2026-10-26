<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StockTakeRequest extends FormRequest
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
            'stock_take_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'lines.*.warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'lines.*.location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'lines.*.system_qty' => ['required', 'numeric', 'decimal:0,4', 'gte:0', 'max:99999999999999'],
            'lines.*.physical_qty' => ['required', 'numeric', 'decimal:0,4', 'gte:0', 'max:99999999999999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $seen = [];

                $submittedLines = $this->input('lines', []);
                foreach (is_array($submittedLines) ? $submittedLines : [] as $index => $line) {
                    if (! is_array($line)) {
                        continue;
                    }
                    $itemId = $line['item_id'] ?? null;
                    $warehouseId = $line['warehouse_id'] ?? null;
                    $locationId = $line['location_id'] ?? null;
                    if (! $itemId || ! $warehouseId) {
                        continue;
                    }

                    if ($locationId && ! Location::query()
                        ->whereKey($locationId)
                        ->where('warehouse_id', $warehouseId)
                        ->where('is_active', true)
                        ->exists()) {
                        $validator->errors()->add("lines.{$index}.location_id", 'Lokasi harus aktif dan berada di gudang pada baris ini.');
                    }

                    $key = $itemId.'|'.$warehouseId.'|'.($locationId ?: 'null');
                    if (isset($seen[$key])) {
                        $validator->errors()->add("lines.{$index}.item_id", 'Barang, gudang, dan lokasi yang sama hanya boleh dihitung sekali.');
                    }
                    $seen[$key] = true;
                }
            },
        ];
    }
}
