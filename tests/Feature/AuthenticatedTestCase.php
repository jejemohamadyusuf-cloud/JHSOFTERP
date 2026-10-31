<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class AuthenticatedTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('chart_of_accounts')) {
            $this->seed(ChartOfAccountsSeeder::class);
        }

        $this->actingAs(User::factory()->make(['role' => 'admin']));
    }
}
