<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Location;
use App\Models\MasterPrice;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $units = [];

            foreach ([
                ['code' => 'PCS', 'name' => 'Pcs'],
                ['code' => 'LEMBAR', 'name' => 'Lembar'],
                ['code' => 'BATANG', 'name' => 'Batang'],
                ['code' => 'KG', 'name' => 'Kilogram'],
            ] as $attributes) {
                $units[$attributes['code']] = Unit::query()->updateOrCreate(
                    ['code' => $attributes['code']],
                    ['name' => $attributes['name']],
                );
            }

            $categories = [];

            foreach ([
                ['code' => 'BAHAN-BAKU', 'name' => 'Bahan Baku'],
                ['code' => 'WIP', 'name' => 'Barang Setengah Jadi'],
                ['code' => 'PRODUK-JADI', 'name' => 'Produk Jadi'],
                ['code' => 'AKSESORIS', 'name' => 'Aksesoris'],
            ] as $attributes) {
                $categories[$attributes['code']] = ItemCategory::query()->updateOrCreate(
                    ['code' => $attributes['code']],
                    ['name' => $attributes['name']],
                );
            }

            foreach ([
                [
                    'code' => 'CUST-001',
                    'name' => 'PT Maju Bersama',
                    'email' => 'purchasing@maju-bersama.test',
                    'phone' => '021-555-0101',
                    'address' => 'Jl. Industri Raya No. 10, Jakarta',
                    'is_active' => true,
                ],
                [
                    'code' => 'CUST-002',
                    'name' => 'CV Sumber Niaga',
                    'email' => 'order@sumber-niaga.test',
                    'phone' => '022-555-0102',
                    'address' => 'Jl. Asia Afrika No. 25, Bandung',
                    'is_active' => true,
                ],
                [
                    'code' => 'CUST-003',
                    'name' => 'Toko Furnitur Sentosa',
                    'email' => null,
                    'phone' => '031-555-0103',
                    'address' => 'Jl. Pahlawan No. 18, Surabaya',
                    'is_active' => true,
                ],
            ] as $attributes) {
                Customer::query()->updateOrCreate(['code' => $attributes['code']], $attributes);
            }

            foreach ([
                [
                    'code' => 'SUP-001',
                    'name' => 'PT Kayu Nusantara',
                    'email' => 'sales@kayu-nusantara.test',
                    'phone' => '021-555-0201',
                    'address' => 'Kawasan Industri Pulogadung, Jakarta',
                    'is_active' => true,
                ],
                [
                    'code' => 'SUP-002',
                    'name' => 'CV Baja Makmur',
                    'email' => 'info@baja-makmur.test',
                    'phone' => '022-555-0202',
                    'address' => 'Jl. Soekarno Hatta No. 80, Bandung',
                    'is_active' => true,
                ],
                [
                    'code' => 'SUP-003',
                    'name' => 'UD Baut Jaya',
                    'email' => null,
                    'phone' => '031-555-0203',
                    'address' => 'Jl. Margomulyo No. 12, Surabaya',
                    'is_active' => true,
                ],
            ] as $attributes) {
                Supplier::query()->updateOrCreate(['code' => $attributes['code']], $attributes);
            }

            $warehouse = Warehouse::query()->updateOrCreate(
                ['code' => 'GUD-PUSAT'],
                [
                    'name' => 'Gudang Pusat',
                    'address' => 'Kawasan Industri Jababeka, Cikarang',
                    'is_active' => true,
                ],
            );

            foreach ([
                ['code' => 'RM-A01', 'name' => 'Rak Bahan Baku A01'],
                ['code' => 'RM-A02', 'name' => 'Rak Bahan Baku A02'],
                ['code' => 'WIP-B01', 'name' => 'Area Barang Setengah Jadi B01'],
                ['code' => 'FG-C01', 'name' => 'Rak Barang Jadi C01'],
            ] as $attributes) {
                Location::query()->updateOrCreate(
                    ['warehouse_id' => $warehouse->id, 'code' => $attributes['code']],
                    ['name' => $attributes['name'], 'is_active' => true],
                );
            }

            $items = [];

            foreach ([
                [
                    'code' => 'BB-PLY-001',
                    'name' => 'Plywood 18 mm',
                    'item_type' => 'raw',
                    'category_id' => $categories['BAHAN-BAKU']->id,
                    'unit_id' => $units['LEMBAR']->id,
                    'standard_cost' => 285000,
                    'is_active' => true,
                ],
                [
                    'code' => 'BB-BESI-001',
                    'name' => 'Besi Hollow 4 x 4 cm',
                    'item_type' => 'raw',
                    'category_id' => $categories['BAHAN-BAKU']->id,
                    'unit_id' => $units['BATANG']->id,
                    'standard_cost' => 95000,
                    'is_active' => true,
                ],
                [
                    'code' => 'BB-BAUT-001',
                    'name' => 'Baut dan Sekrup Set',
                    'item_type' => 'raw',
                    'category_id' => $categories['AKSESORIS']->id,
                    'unit_id' => $units['PCS']->id,
                    'standard_cost' => 1200,
                    'is_active' => true,
                ],
                [
                    'code' => 'WIP-MEJA-001',
                    'name' => 'Rangka Meja Kerja',
                    'item_type' => 'semi',
                    'category_id' => $categories['WIP']->id,
                    'unit_id' => $units['PCS']->id,
                    'standard_cost' => 240000,
                    'is_active' => true,
                ],
                [
                    'code' => 'FG-MEJA-001',
                    'name' => 'Meja Kerja Industri',
                    'item_type' => 'finished',
                    'category_id' => $categories['PRODUK-JADI']->id,
                    'unit_id' => $units['PCS']->id,
                    'standard_cost' => 675000,
                    'is_active' => true,
                ],
            ] as $attributes) {
                $items[$attributes['code']] = Item::query()->updateOrCreate(
                    ['code' => $attributes['code']],
                    $attributes,
                );
            }

            foreach ($items as $code => $item) {
                MasterPrice::query()->updateOrCreate(
                    ['item_id' => $item->id, 'start_date' => '2026-10-01'],
                    [
                        'price' => match ($code) {
                            'BB-PLY-001' => 375000,
                            'BB-BESI-001' => 145000,
                            'BB-BAUT-001' => 2000,
                            'WIP-MEJA-001' => 325000,
                            'FG-MEJA-001' => 950000,
                        },
                        'end_date' => null,
                    ],
                );
            }

            $bom = Bom::query()->updateOrCreate(
                ['item_id' => $items['FG-MEJA-001']->id, 'version' => '1'],
                ['is_active' => true],
            );

            foreach ([
                [
                    'item' => $items['BB-PLY-001'],
                    'qty' => 1,
                    'unit_id' => $units['LEMBAR']->id,
                    'scrap_percent' => 5,
                ],
                [
                    'item' => $items['WIP-MEJA-001'],
                    'qty' => 1,
                    'unit_id' => $units['PCS']->id,
                    'scrap_percent' => 0,
                ],
                [
                    'item' => $items['BB-BAUT-001'],
                    'qty' => 12,
                    'unit_id' => $units['PCS']->id,
                    'scrap_percent' => 2,
                ],
            ] as $component) {
                $bom->lines()->updateOrCreate(
                    ['component_item_id' => $component['item']->id],
                    [
                        'qty' => $component['qty'],
                        'unit_id' => $component['unit_id'],
                        'scrap_percent' => $component['scrap_percent'],
                    ],
                );
            }

            $this->command?->info('Dummy master data berhasil dibuat atau diperbarui.');
        });
    }
}
