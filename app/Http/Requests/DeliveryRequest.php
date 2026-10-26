<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DeliveryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $input = $this->input('lines', []);
        if (! is_array($input)) {
            return;
        }

        $lines = collect($input)
            ->filter(fn ($line): bool => is_array($line) && isset($line['qty']) && $line['qty'] !== '')
            ->values()
            ->all();

        $this->merge(['lines' => $lines]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_order_id' => ['required', 'integer', Rule::exists('sales_orders', 'id')->where('status', 'confirmed')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_line_id' => ['required', 'integer', 'exists:sales_order_lines,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'lines.*.location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
        ];
    }
}
