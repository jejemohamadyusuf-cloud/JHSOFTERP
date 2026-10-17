<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\MasterDataCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

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

        return $definition['model']::query()->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(string $key, array $attributes): Model
    {
        $definition = $this->definition($key);

        return $definition['model']::query()->create($this->normalize($attributes, $definition));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(string $key, int $id, array $attributes): Model
    {
        $record = $this->find($key, $id);
        $record->update($this->normalize($attributes, $this->definition($key)));

        return $record;
    }

    public function delete(string $key, int $id): void
    {
        $this->find($key, $id)->delete();
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $definition
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
}
