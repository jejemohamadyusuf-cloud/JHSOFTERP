<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\MasterDataCatalog;
use Illuminate\Foundation\Http\FormRequest;

final class MasterDataRequest extends FormRequest
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
        $recordId = $this->route('record') ? (int) $this->route('record') : null;

        return MasterDataCatalog::rules((string) $this->route('master'), $recordId);
    }
}
