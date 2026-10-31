<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\GeneralJournalLine;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class GeneralLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where('is_active', 1)->where('is_header', 0),
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('code')
            ->get();

        $account = isset($filters['account_id'])
            ? $accounts->firstWhere('id', (int) $filters['account_id'])
            : null;

        $lines = null;
        $debitTotal = '0.00';
        $creditTotal = '0.00';

        if ($account !== null) {
            $query = $this->filteredLines((int) $account->id, $filters);
            $totals = (clone $query)
                ->selectRaw('COALESCE(SUM(general_journal_lines.debit_amount), 0) as debit_total')
                ->selectRaw('COALESCE(SUM(general_journal_lines.credit_amount), 0) as credit_total')
                ->first();

            $debitTotal = number_format((float) $totals->debit_total, 2, '.', '');
            $creditTotal = number_format((float) $totals->credit_total, 2, '.', '');

            $lines = $query
                ->with('journalEntry')
                ->orderBy('general_journal_entries.journal_date')
                ->orderBy('general_journal_entries.id')
                ->orderBy('general_journal_lines.id')
                ->select('general_journal_lines.*')
                ->paginate(50)
                ->withQueryString();

            $openingBalance = $this->openingBalance($account, $lines->first());
            $runningBalance = $openingBalance;

            foreach ($lines as $line) {
                $runningBalance += self::toMinorUnits($line->debit_amount)
                    - self::toMinorUnits($line->credit_amount);
                $line->setAttribute('running_balance_amount', number_format(abs($runningBalance) / 100, 2, '.', ''));
                $line->setAttribute('running_balance_side', $runningBalance < 0 ? 'Kredit' : 'Debit');
            }

            $account->setAttribute('opening_balance_amount', number_format(abs($openingBalance) / 100, 2, '.', ''));
            $account->setAttribute('opening_balance_side', $openingBalance < 0 ? 'Kredit' : 'Debit');
        }

        return view('general-ledger.index', [
            'accounts' => $accounts,
            'account' => $account,
            'lines' => $lines,
            'filters' => $filters,
            'debitTotal' => $debitTotal,
            'creditTotal' => $creditTotal,
        ]);
    }

    /**
     * @param  array{account_id?: int, from?: string, to?: string}  $filters
     * @return Builder<GeneralJournalLine>
     */
    private function filteredLines(int $accountId, array $filters): Builder
    {
        $query = GeneralJournalLine::query()
            ->join('general_journal_entries', 'general_journal_entries.id', '=', 'general_journal_lines.general_journal_entry_id')
            ->where('general_journal_lines.chart_of_account_id', $accountId);

        if (! empty($filters['from'])) {
            $query->whereDate('general_journal_entries.journal_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('general_journal_entries.journal_date', '<=', $filters['to']);
        }

        return $query;
    }

    private function openingBalance(ChartOfAccount $account, ?GeneralJournalLine $firstLine): int
    {
        if ($firstLine === null) {
            return 0;
        }

        $firstDate = $firstLine->journalEntry->journal_date->toDateString();
        $firstEntryId = $firstLine->general_journal_entry_id;
        $earlierLines = GeneralJournalLine::query()
            ->where('chart_of_account_id', $account->id)
            ->where(function (Builder $query) use ($firstDate, $firstEntryId, $firstLine): void {
                $query->whereHas('journalEntry', function (Builder $entries) use ($firstDate, $firstEntryId): void {
                    $entries->whereDate('journal_date', '<', $firstDate)
                        ->orWhere(function (Builder $sameDate) use ($firstDate, $firstEntryId): void {
                            $sameDate->whereDate('journal_date', $firstDate)
                                ->where('id', '<', $firstEntryId);
                        });
                })->orWhere(function (Builder $sameEntry) use ($firstEntryId, $firstLine): void {
                    $sameEntry->where('general_journal_entry_id', $firstEntryId)
                        ->where('id', '<', $firstLine->id);
                });
            });

        return self::toMinorUnits((string) (clone $earlierLines)->sum('debit_amount'))
            - self::toMinorUnits((string) (clone $earlierLines)->sum('credit_amount'));
    }

    private static function toMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
