<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\PicGuideCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PicGuideRequest extends FormRequest
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
        $rules = [];

        foreach (array_keys(PicGuideCatalog::modules()) as $module) {
            $rules["guides.{$module}.department"] = ['required', 'string', 'max:120'];
            $rules["guides.{$module}.input_role"] = ['required', 'string', 'max:120'];
            $rules["guides.{$module}.review_role"] = ['required', 'string', 'max:120'];
            $rules["guides.{$module}.is_enabled"] = ['required', Rule::in(['0', '1', 0, 1, false, true])];
        }

        return $rules;
    }
}
