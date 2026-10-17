<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

final class MasterDataCatalog
{
    /**
     * @return array<string, array{
     *     label: string,
     *     model: class-string<Model>,
     *     columns: array<string, string>,
     *     fields: array<string, array<string, mixed>>
     * }>
     */
    public static function all(): array
    {
        return [
            'items' => [
                'label' => 'Barang',
                'model' => Item::class,
                'columns' => ['code' => 'Kode', 'name' => 'Nama', 'item_type' => 'Jenis', 'unit.name' => 'Satuan', 'is_active' => 'Status'],
                'fields' => [
                    'code' => ['label' => 'Kode barang', 'type' => 'text', 'required' => true, 'max' => 40],
                    'name' => ['label' => 'Nama barang', 'type' => 'text', 'required' => true, 'max' => 255],
                    'item_type' => ['label' => 'Jenis barang', 'type' => 'select', 'required' => true, 'options' => ['raw' => 'Bahan baku', 'semi' => 'Barang setengah jadi', 'finished' => 'Barang jadi']],
                    'category_id' => ['label' => 'Kategori', 'type' => 'relation', 'required' => false, 'model' => ItemCategory::class],
                    'unit_id' => ['label' => 'Satuan', 'type' => 'relation', 'required' => true, 'model' => Unit::class],
                    'standard_cost' => ['label' => 'Biaya standar', 'type' => 'number', 'required' => true, 'step' => '0.0001', 'min' => 0],
                    'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'required' => false],
                ],
            ],
            'item-categories' => [
                'label' => 'Kategori Barang',
                'model' => ItemCategory::class,
                'columns' => ['code' => 'Kode', 'name' => 'Nama'],
                'fields' => [
                    'code' => ['label' => 'Kode kategori', 'type' => 'text', 'required' => true, 'max' => 30],
                    'name' => ['label' => 'Nama kategori', 'type' => 'text', 'required' => true, 'max' => 255],
                ],
            ],
            'units' => [
                'label' => 'Satuan',
                'model' => Unit::class,
                'columns' => ['code' => 'Kode', 'name' => 'Nama'],
                'fields' => [
                    'code' => ['label' => 'Kode satuan', 'type' => 'text', 'required' => true, 'max' => 20],
                    'name' => ['label' => 'Nama satuan', 'type' => 'text', 'required' => true, 'max' => 255],
                ],
            ],
            'customers' => self::partner(Customer::class, 'Pelanggan'),
            'suppliers' => self::partner(Supplier::class, 'Supplier'),
            'warehouses' => [
                'label' => 'Gudang',
                'model' => Warehouse::class,
                'columns' => ['code' => 'Kode', 'name' => 'Nama', 'address' => 'Alamat', 'is_active' => 'Status'],
                'fields' => [
                    'code' => ['label' => 'Kode gudang', 'type' => 'text', 'required' => true, 'max' => 30],
                    'name' => ['label' => 'Nama gudang', 'type' => 'text', 'required' => true, 'max' => 255],
                    'address' => ['label' => 'Alamat', 'type' => 'textarea', 'required' => false],
                    'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'required' => false],
                ],
            ],
            'locations' => [
                'label' => 'Lokasi Gudang',
                'model' => Location::class,
                'columns' => ['warehouse.name' => 'Gudang', 'code' => 'Kode lokasi', 'name' => 'Nama', 'is_active' => 'Status'],
                'fields' => [
                    'warehouse_id' => ['label' => 'Gudang', 'type' => 'relation', 'required' => true, 'model' => Warehouse::class],
                    'code' => ['label' => 'Kode lokasi', 'type' => 'text', 'required' => true, 'max' => 40],
                    'name' => ['label' => 'Nama lokasi', 'type' => 'text', 'required' => true, 'max' => 255],
                    'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'required' => false],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(string $key): array
    {
        abort_unless(isset(self::all()[$key]), 404);

        return self::all()[$key];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(string $key, ?int $recordId = null): array
    {
        $master = self::get($key);
        $rules = [];

        foreach ($master['fields'] as $field => $definition) {
            $required = $definition['required'] ? 'required' : 'nullable';

            if ($definition['type'] === 'relation') {
                $table = (new $definition['model']())->getTable();
                $rules[$field] = [$required, 'integer', Rule::exists($table, 'id')];

                continue;
            }

            if ($definition['type'] === 'checkbox') {
                $rules[$field] = ['sometimes', 'boolean'];

                continue;
            }

            if ($field === 'code') {
                $table = (new $master['model']())->getTable();
                $unique = Rule::unique($table, 'code')->ignore($recordId);

                if ($key === 'locations') {
                    $unique->where('warehouse_id', request()->input('warehouse_id'));
                }

                $rules[$field] = [$required, 'string', 'max:'.$definition['max'], $unique];

                continue;
            }

            if ($definition['type'] === 'select') {
                $rules[$field] = [$required, Rule::in(array_keys($definition['options']))];

                continue;
            }

            if ($definition['type'] === 'number') {
                $rules[$field] = [$required, 'numeric', 'min:'.$definition['min']];

                continue;
            }

            if ($definition['type'] === 'email') {
                $rules[$field] = [$required, 'email', 'max:'.$definition['max']];

                continue;
            }

            $rules[$field] = [$required, 'string'];

            if (isset($definition['max'])) {
                $rules[$field][] = 'max:'.$definition['max'];
            }
        }

        return $rules;
    }

    /**
     * @param class-string<Model> $model
     * @return array<string, mixed>
     */
    private static function partner(string $model, string $label): array
    {
        return [
            'label' => $label,
            'model' => $model,
            'columns' => ['code' => 'Kode', 'name' => 'Nama', 'email' => 'Email', 'phone' => 'Telepon', 'is_active' => 'Status'],
            'fields' => [
                'code' => ['label' => 'Kode', 'type' => 'text', 'required' => true, 'max' => 30],
                'name' => ['label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 255],
                'email' => ['label' => 'Email', 'type' => 'email', 'required' => false, 'max' => 255],
                'phone' => ['label' => 'Telepon', 'type' => 'text', 'required' => false, 'max' => 30],
                'address' => ['label' => 'Alamat', 'type' => 'textarea', 'required' => false],
                'is_active' => ['label' => 'Aktif', 'type' => 'checkbox', 'required' => false],
            ],
        ];
    }
}
