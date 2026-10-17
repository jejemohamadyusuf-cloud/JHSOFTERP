<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BomRequest extends FormRequest
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
        $bomId = $this->route('bom') ? (int) $this->route('bom') : null;

        return [
            'item_id' => ['required', 'integer', Rule::exists('items', 'id'), Rule::unique('boms', 'item_id')->where('version', $this->input('version', '1'))->ignore($bomId)],
            'version' => ['required', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.component_item_id' => ['required', 'integer', 'distinct', 'different:item_id', Rule::exists('items', 'id')],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'lines.*.scrap_percent' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
