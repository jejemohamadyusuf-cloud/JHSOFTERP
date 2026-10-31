<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_hierarchical_chart_of_accounts_idempotently(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
        $firstCount = ChartOfAccount::query()->count();

        $this->seed(ChartOfAccountsSeeder::class);

        $this->assertSame($firstCount, ChartOfAccount::query()->count());
        $this->assertSame('1130', ChartOfAccount::query()->where('code', '1131')->firstOrFail()->parent->code);
        $this->assertTrue(ChartOfAccount::query()->where('code', '1130')->firstOrFail()->is_header);
    }

    public function test_finance_can_manage_coa_but_cannot_manage_operational_masters(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'finance']))
            ->get(route('master-data.index', 'chart-of-accounts'))
            ->assertOk()
            ->assertSee('Chart of Accounts (COA)');

        $this->get(route('master-data.index', 'items'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'sales']))
            ->get(route('master-data.index', 'chart-of-accounts'))
            ->assertForbidden();
    }

    public function test_an_account_cannot_be_moved_under_its_own_descendant(): void
    {
        $root = ChartOfAccount::query()->create([
            'code' => '9000',
            'name' => 'Akun Induk',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_header' => true,
        ]);
        $child = ChartOfAccount::query()->create([
            'code' => '9001',
            'name' => 'Akun Turunan',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => $root->id,
            'is_header' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'finance']))
            ->put(route('master-data.update', ['master' => 'chart-of-accounts', 'record' => $root->id]), [
                'code' => $root->code,
                'name' => $root->name,
                'account_type' => $root->account_type,
                'normal_balance' => $root->normal_balance,
                'parent_id' => $child->id,
                'is_header' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('parent_id');
    }
}
