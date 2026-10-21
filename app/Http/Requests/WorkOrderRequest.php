<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class WorkOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'production_type' => $this->input('production_type', 'make_to_order'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $productionType = $this->input('production_type', 'make_to_order');

        return [
            'production_type' => ['required', Rule::in(['make_to_order', 'make_to_stock'])],
            'sales_order_line_id' => [
                Rule::requiredIf($productionType === 'make_to_order'),
                'nullable',
                'integer',
                Rule::exists('sales_order_lines', 'id'),
            ],
            'item_id' => [
                Rule::requiredIf($productionType === 'make_to_stock'),
                'nullable',
                'integer',
                Rule::exists('items', 'id')->where('is_active', true),
            ],
            'bom_id' => ['required', 'integer', Rule::exists('boms', 'id')->where('is_active', true)],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'qty_planned' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
