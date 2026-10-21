<?php

declare(strict_types=1);

namespace App\Support;

final class PicGuideCatalog
{
    /**
     * @return array<string, string>
     */
    public static function modules(): array
    {
        return [
            'items' => 'Barang',
            'item-categories' => 'Kategori Barang',
            'units' => 'Satuan',
            'customers' => 'Pelanggan',
            'suppliers' => 'Supplier',
            'warehouses' => 'Gudang',
            'locations' => 'Lokasi Gudang',
            'master-prices' => 'Master Harga',
            'boms' => 'Bill of Materials',
            'sales-orders' => 'Sales Order',
            'work-orders' => 'Work Order',
            'purchase-orders' => 'Purchase Order',
            'stock' => 'Stok',
        ];
    }
}
