<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Inventory = 'inventory';
    case Production = 'production';
    case Finance = 'finance';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Sales => 'Penjualan',
            self::Inventory => 'Persediaan & Pembelian',
            self::Production => 'Produksi',
            self::Finance => 'Keuangan',
        };
    }
}
