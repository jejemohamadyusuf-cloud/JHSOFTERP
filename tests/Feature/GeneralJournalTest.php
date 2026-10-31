<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\GeneralJournalEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GeneralJournalTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_post_balanced_journal_and_view_account_ledger(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $cash = ChartOfAccount::query()->where('code', '1112')->firstOrFail();
        $sales = ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'finance']))
            ->get(route('general-journal.create'))
            ->assertOk()
            ->assertSee('Chart of Accounts (COA)')
            ->assertSee('General Journal')
            ->assertSee('General Ledger');

        $this->post(route('general-journal.store'), [
            'journal_date' => '2026-10-28',
            'reference' => 'TRF-001',
            'description' => 'Penerimaan penjualan',
            'lines' => [
                [
                    'chart_of_account_id' => $cash->id,
                    'description' => 'Penerimaan bank',
                    'debit_amount' => '125000.00',
                    'credit_amount' => '0.00',
                ],
                [
                    'chart_of_account_id' => $sales->id,
                    'description' => 'Pendapatan penjualan',
                    'debit_amount' => '0.00',
                    'credit_amount' => '125000.00',
                ],
            ],
        ])->assertRedirect();

        $journal = GeneralJournalEntry::query()->firstOrFail();
        $this->assertSame('GJ-20261028-000001', $journal->journal_number);
        $this->assertSame(2, $journal->lines()->count());
        $this->assertDatabaseHas('general_journal_lines', ['chart_of_account_id' => $cash->id]);
        $this->assertSame(1, DB::table('general_journal_lines')
            ->join('general_journal_entries', 'general_journal_entries.id', '=', 'general_journal_lines.general_journal_entry_id')
            ->where('general_journal_lines.chart_of_account_id', $cash->id)
            ->whereDate('general_journal_entries.journal_date', '2026-10-28')
            ->count());
        $this->get(route('general-journal.index'))
            ->assertOk()
            ->assertSee($journal->journal_number);
        $this->get(route('general-journal.show', $journal))
            ->assertOk()
            ->assertSee('Penerimaan penjualan')
            ->assertSee('125.000,00');

        $this->get(route('general-ledger.index', [
            'account_id' => $cash->id,
            'from' => '2026-10-28',
            'to' => '2026-10-28',
        ]))
            ->assertOk()
            ->assertSee('Penerimaan bank')
            ->assertSee('125.000,00 Debit');

        $this->get(route('general-ledger.index', ['account_id' => $sales->id]))
            ->assertOk()
            ->assertSee('125.000,00 Kredit');
    }

    public function test_unbalanced_journal_is_rejected_without_posting(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'finance']))
            ->post(route('general-journal.store'), [
                'journal_date' => '2026-10-28',
                'description' => 'Jurnal tidak seimbang',
                'lines' => [
                    [
                        'chart_of_account_id' => ChartOfAccount::query()->where('code', '1112')->value('id'),
                        'debit_amount' => '100.00',
                        'credit_amount' => '0.00',
                    ],
                    [
                        'chart_of_account_id' => ChartOfAccount::query()->where('code', '4100')->value('id'),
                        'debit_amount' => '0.00',
                        'credit_amount' => '99.00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('general_journal_entries', 0);
    }

    public function test_general_journal_and_ledger_are_restricted_to_finance_and_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'sales']));

        $this->get(route('general-journal.index'))->assertForbidden();
        $this->get(route('general-ledger.index'))->assertForbidden();
    }
}
