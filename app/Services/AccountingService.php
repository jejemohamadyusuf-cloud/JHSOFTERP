<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\GeneralJournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class AccountingService
{
    /**
     * @param  array<int, array{account_code: string, debit_cents: int, credit_cents: int, description?: string|null}>  $lines
     */
    public function post(
        Model $source,
        string $date,
        string $reference,
        string $description,
        array $lines,
        ?int $createdBy = null,
    ): ?GeneralJournalEntry {
        $existing = GeneralJournalEntry::query()
            ->where('source_type', $source::class)
            ->where('source_id', $source->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $lines = array_values(array_filter(
            $lines,
            static fn (array $line): bool => $line['debit_cents'] > 0 || $line['credit_cents'] > 0,
        ));

        if ($lines === []) {
            return null;
        }

        $debitTotal = array_sum(array_column($lines, 'debit_cents'));
        $creditTotal = array_sum(array_column($lines, 'credit_cents'));
        if ($debitTotal <= 0 || $debitTotal !== $creditTotal) {
            throw new LogicException('Jurnal otomatis untuk '.$source::class.' harus seimbang dan bernilai positif.');
        }

        return DB::transaction(function () use ($source, $date, $reference, $description, $lines, $createdBy): GeneralJournalEntry {
            $existing = GeneralJournalEntry::query()
                ->where('source_type', $source::class)
                ->where('source_id', $source->getKey())
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $entry = GeneralJournalEntry::query()->create([
                'journal_number' => 'TMP-'.Str::uuid(),
                'journal_date' => $date,
                'reference' => $reference,
                'description' => $description,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
                'created_by' => $createdBy ?? auth()->id(),
                'posted_at' => now(),
            ]);

            $entry->update([
                'journal_number' => sprintf('GJ-%s-%06d', $entry->journal_date->format('Ymd'), $entry->id),
            ]);

            $accountIds = ChartOfAccount::query()
                ->whereIn('code', array_unique(array_column($lines, 'account_code')))
                ->where('is_active', true)
                ->where('is_header', false)
                ->pluck('id', 'code');

            foreach ($lines as $line) {
                if (! isset($accountIds[$line['account_code']])) {
                    throw new LogicException("Akun COA {$line['account_code']} tidak tersedia atau tidak aktif.");
                }

                $entry->lines()->create([
                    'chart_of_account_id' => $accountIds[$line['account_code']],
                    'description' => $line['description'] ?? null,
                    'debit_amount' => number_format($line['debit_cents'] / 100, 2, '.', ''),
                    'credit_amount' => number_format($line['credit_cents'] / 100, 2, '.', ''),
                ]);
            }

            return $entry;
        });
    }

    public static function cents(float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function valueCents(float|string $quantity, float|string $unitCost): int
    {
        return (int) round((float) $quantity * (float) $unitCost * 100);
    }

    public function inventoryAccountCode(string $itemType): string
    {
        return match ($itemType) {
            'raw' => '1131',
            'semi' => '1132',
            'finished' => '1133',
            default => throw new LogicException("Jenis barang {$itemType} tidak memiliki akun persediaan."),
        };
    }

    public function cashAccountCode(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash' => '1111',
            'transfer' => '1112',
            default => throw new LogicException("Metode pembayaran {$paymentMethod} belum dipetakan ke akun kas/bank."),
        };
    }
}
