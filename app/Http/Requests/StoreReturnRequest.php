<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReturnRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $lines = $this->input('lines', []);
        if (is_array($lines)) {
            $this->merge([
                'lines' => collect($lines)
                    ->filter(fn ($line): bool => is_array($line) && isset($line['qty']) && (float) $line['qty'] > 0)
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_id' => ['required', 'integer'],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source_line_id' => ['required', 'integer', 'distinct'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999999999'],
        ];
    }
}
