<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $accounts = [
                ['code' => '1000', 'name' => 'ASET', 'account_type' => 'asset', 'normal_balance' => 'debit', 'header' => true],
                ['code' => '1100', 'name' => 'Aset Lancar', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000', 'header' => true],
                ['code' => '1110', 'name' => 'Kas dan Setara Kas', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100', 'header' => true],
                ['code' => '1111', 'name' => 'Kas Kecil', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1110'],
                ['code' => '1112', 'name' => 'Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1110'],
                ['code' => '1120', 'name' => 'Piutang Usaha', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
                ['code' => '1130', 'name' => 'Persediaan', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100', 'header' => true],
                ['code' => '1131', 'name' => 'Persediaan Bahan Baku dan Aksesoris', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1130'],
                ['code' => '1132', 'name' => 'Persediaan Barang Dalam Proses', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1130'],
                ['code' => '1133', 'name' => 'Persediaan Barang Jadi', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1130'],
                ['code' => '1140', 'name' => 'Pajak Masukan', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
                ['code' => '1150', 'name' => 'Uang Muka Pembelian', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
                ['code' => '1200', 'name' => 'Aset Tetap', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000', 'header' => true],
                ['code' => '1210', 'name' => 'Mesin dan Peralatan Produksi', 'account_type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1200'],
                ['code' => '1220', 'name' => 'Akumulasi Penyusutan', 'account_type' => 'asset', 'normal_balance' => 'credit', 'parent' => '1200'],

                ['code' => '2000', 'name' => 'LIABILITAS', 'account_type' => 'liability', 'normal_balance' => 'credit', 'header' => true],
                ['code' => '2100', 'name' => 'Liabilitas Jangka Pendek', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000', 'header' => true],
                ['code' => '2110', 'name' => 'Utang Usaha', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
                ['code' => '2120', 'name' => 'Utang Beban', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
                ['code' => '2130', 'name' => 'Pajak Keluaran', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
                ['code' => '2140', 'name' => 'Uang Muka Pelanggan', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
                ['code' => '2190', 'name' => 'Goods Received Not Invoiced (GRNI)', 'account_type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],

                ['code' => '3000', 'name' => 'EKUITAS', 'account_type' => 'equity', 'normal_balance' => 'credit', 'header' => true],
                ['code' => '3100', 'name' => 'Modal Pemilik', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent' => '3000'],
                ['code' => '3200', 'name' => 'Saldo Laba', 'account_type' => 'equity', 'normal_balance' => 'credit', 'parent' => '3000'],

                ['code' => '4000', 'name' => 'PENDAPATAN', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'header' => true],
                ['code' => '4100', 'name' => 'Penjualan', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'parent' => '4000'],
                ['code' => '4110', 'name' => 'Retur dan Potongan Penjualan', 'account_type' => 'revenue', 'normal_balance' => 'debit', 'parent' => '4000'],
                ['code' => '4200', 'name' => 'Pendapatan Lain-lain', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'parent' => '4000'],

                ['code' => '5000', 'name' => 'HARGA POKOK PENJUALAN DAN BIAYA PRODUKSI', 'account_type' => 'expense', 'normal_balance' => 'debit', 'header' => true],
                ['code' => '5100', 'name' => 'Harga Pokok Penjualan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
                ['code' => '5200', 'name' => 'Pemakaian Bahan Baku Produksi', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
                ['code' => '5300', 'name' => 'Biaya Tenaga Kerja Langsung', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
                ['code' => '5400', 'name' => 'Biaya Overhead Pabrik', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],

                ['code' => '6000', 'name' => 'BEBAN OPERASIONAL', 'account_type' => 'expense', 'normal_balance' => 'debit', 'header' => true],
                ['code' => '6100', 'name' => 'Beban Gaji dan Administrasi', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '6000'],
                ['code' => '6200', 'name' => 'Beban Penjualan dan Pemasaran', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '6000'],
                ['code' => '6300', 'name' => 'Beban Penyusutan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '6000'],
                ['code' => '6400', 'name' => 'Beban Selisih dan Penyesuaian Persediaan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '6000'],
                ['code' => '6500', 'name' => 'Beban Pengiriman', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '6000'],

                ['code' => '7000', 'name' => 'BEBAN LAIN-LAIN', 'account_type' => 'expense', 'normal_balance' => 'debit', 'header' => true],
                ['code' => '7100', 'name' => 'Beban Administrasi Bank', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '7000'],
                ['code' => '7200', 'name' => 'Beban Bunga', 'account_type' => 'expense', 'normal_balance' => 'debit', 'parent' => '7000'],
            ];

            foreach ($accounts as $account) {
                $parent = isset($account['parent'])
                    ? ChartOfAccount::query()->where('code', $account['parent'])->firstOrFail()
                    : null;

                ChartOfAccount::query()->updateOrCreate(
                    ['code' => $account['code']],
                    [
                        'name' => $account['name'],
                        'account_type' => $account['account_type'],
                        'normal_balance' => $account['normal_balance'],
                        'parent_id' => $parent?->id,
                        'is_header' => $account['header'] ?? false,
                        'is_active' => true,
                    ],
                );
            }
        });

        $this->command?->info('Master COA berhasil dibuat atau diperbarui.');
    }
}
