<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use App\Support\MasterDataCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        if ($this->route('master') === 'chart-of-accounts') {
            return [
                function (Validator $validator): void {
                    $recordId = (int) $this->route('record');
                    $parentId = $this->input('parent_id');
                    if (! $recordId || ! $parentId) {
                        return;
                    }

                    $ancestor = ChartOfAccount::query()->find($parentId);
                    while ($ancestor !== null) {
                        if ($ancestor->id === $recordId) {
                            $validator->errors()->add('parent_id', 'Induk akun tidak boleh menjadi akun itu sendiri atau turunannya.');

                            return;
                        }

                        $ancestor = $ancestor->parent;
                    }
                },
            ];
        }

        if ($this->route('master') !== 'items') {
            return [];
        }

        return [
            function (Validator $validator): void {
                $baseUnitId = (string) $this->input('unit_id');
                $seenUnits = [];
                $seenRatios = [];
                $conversions = [];

                $submittedConversions = $this->input('conversions', []);
                foreach (is_array($submittedConversions) ? $submittedConversions : [] as $index => $conversion) {
                    if (! is_array($conversion) || empty($conversion['unit_id']) || empty($conversion['base_units'])) {
                        continue;
                    }

                    $unitId = (string) $conversion['unit_id'];
                    if ($unitId === $baseUnitId) {
                        $validator->errors()->add("conversions.{$index}.unit_id", 'Satuan kemasan harus berbeda dari satuan dasar barang.');
                    }
                    if (isset($seenUnits[$unitId])) {
                        $validator->errors()->add("conversions.{$index}.unit_id", 'Satuan kemasan tidak boleh dipilih lebih dari sekali.');
                    }
                    $seenUnits[$unitId] = true;
                    $ratio = (int) $conversion['base_units'];
                    if (isset($seenRatios[$ratio])) {
                        $validator->errors()->add("conversions.{$index}.base_units", 'Setiap satuan kemasan harus memiliki rasio yang berbeda.');
                    }
                    $seenRatios[$ratio] = true;
                    $conversions[] = [
                        'index' => $index,
                        'base_units' => $ratio,
                    ];
                }

                usort($conversions, fn (array $left, array $right): int => $right['base_units'] <=> $left['base_units']);
                for ($index = 1; $index < count($conversions); $index++) {
                    $larger = $conversions[$index - 1];
                    $smaller = $conversions[$index];
                    if ($smaller['base_units'] === 0 || $larger['base_units'] % $smaller['base_units'] !== 0) {
                        $validator->errors()->add(
                            "conversions.{$larger['index']}.base_units",
                            'Rasio kemasan yang lebih besar harus merupakan kelipatan rasio kemasan di bawahnya.',
                        );
                    }
                }
            },
        ];
    }
}
