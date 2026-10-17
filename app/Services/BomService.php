<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class BomService
{
    /**
     * @return LengthAwarePaginator<int, Bom>
     */
    public function paginate(): LengthAwarePaginator
    {
        return Bom::query()->with(['item', 'lines.component', 'lines.unit'])->latest('id')->paginate(15);
    }

    public function find(int $id): Bom
    {
        return Bom::query()->with(['item', 'lines.component', 'lines.unit'])->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function save(array $attributes, ?int $id = null): Bom
    {
        return DB::transaction(function () use ($attributes, $id): Bom {
            $bom = $id === null ? new Bom() : Bom::query()->findOrFail($id);
            $bom->fill([
                'item_id' => $attributes['item_id'],
                'version' => $attributes['version'],
                'is_active' => $attributes['is_active'] ?? false,
            ]);
            $bom->save();

            $bom->lines()->delete();
            $bom->lines()->createMany($attributes['lines']);

            return $bom;
        });
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
    }
}
