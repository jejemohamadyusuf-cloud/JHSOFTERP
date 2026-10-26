<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\MasterDataCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class MasterDataService
{
    /**
     * @return array<string, mixed>
     */
    public function definition(string $key): array
    {
        return MasterDataCatalog::get($key);
    }

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(string $key): LengthAwarePaginator
    {
        $definition = $this->definition($key);
        $query = $definition['model']::query();

        foreach ($definition['columns'] as $column => $_label) {
            if (str_contains($column, '.')) {
                $query->with(strstr($column, '.', true));
            }
        }

        return $query->latest('id')->paginate(15)->withQueryString();
    }

    public function find(string $key, int $id): Model
    {
        $definition = $this->definition($key);

        $query = $definition['model']::query();
        if ($key === 'items') {
            $query->with('unitConversions');
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $key, array $attributes): Model
    {
        $definition = $this->definition($key);

        return DB::transaction(function () use ($key, $attributes, $definition): Model {
            $conversions = $attributes['conversions'] ?? [];
            unset($attributes['conversions']);

            $record = $definition['model']::query()->create($this->normalize($attributes, $definition));
            if ($key === 'items') {
                $this->syncItemConversions($record, $conversions);
            }

            return $record;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $key, int $id, array $attributes): Model
    {
        $record = DB::transaction(function () use ($key, $id, $attributes): Model {
            $definition = $this->definition($key);
            $conversions = $attributes['conversions'] ?? [];
            unset($attributes['conversions']);

            $record = $this->find($key, $id);
            $record->update($this->normalize($attributes, $definition));
            if ($key === 'items') {
                $this->syncItemConversions($record, $conversions);
            }

            return $record;
        });

        return $record;
    }

    public function delete(string $key, int $id): void
    {
        $this->find($key, $id)->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function normalize(array $attributes, array $definition): array
    {
        foreach ($definition['fields'] as $field => $fieldDefinition) {
            if ($fieldDefinition['type'] === 'checkbox') {
                $attributes[$field] = (bool) ($attributes[$field] ?? false);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<int, array{unit_id: int|string, base_units: int|string}>  $conversions
     */
    private function syncItemConversions(Model $record, array $conversions): void
    {
        $record->unitConversions()->delete();

        foreach ($conversions as $conversion) {
            if (empty($conversion['unit_id']) || empty($conversion['base_units'])) {
                continue;
            }

            $record->unitConversions()->create([
                'unit_id' => $conversion['unit_id'],
                'base_units' => $conversion['base_units'],
            ]);
        }
    }
}
