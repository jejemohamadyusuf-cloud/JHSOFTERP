<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FinishedGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_date' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
        ];
    }
}
