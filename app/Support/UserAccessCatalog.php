<?php

declare(strict_types=1);

namespace App\Support;

final class UserAccessCatalog
{
    /**
     * @return array<string, array{label: string, roles: list<string>}>
     */
    public static function all(): array
    {
        return [
            'master-data' => ['label' => 'Data Master', 'roles' => ['sales', 'inventory', 'finance']],
            'master-prices' => ['label' => 'Master Harga', 'roles' => ['sales', 'inventory']],
            'boms' => ['label' => 'Bill of Materials', 'roles' => ['inventory', 'production']],
            'purchasing' => ['label' => 'Purchase Order & Receiving', 'roles' => ['inventory']],
            'stock' => ['label' => 'Stok & Stock Take', 'roles' => ['inventory']],
            'sales-orders' => ['label' => 'Sales Order', 'roles' => ['sales']],
            'sales-returns' => ['label' => 'Retur Penjualan', 'roles' => ['sales']],
            'deliveries' => ['label' => 'Delivery', 'roles' => ['sales', 'finance']],
            'purchase-returns' => ['label' => 'Retur Pembelian', 'roles' => ['inventory']],
            'finance' => ['label' => 'Keuangan', 'roles' => ['finance']],
            'production' => ['label' => 'Work Order', 'roles' => ['production']],
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultsForRole(string $role): array
    {
        $permissions = [];

        foreach (self::all() as $key => $permission) {
            if (in_array($role, $permission['roles'], true)) {
                $permissions[] = $key;
            }
        }

        return $permissions;
    }
}
