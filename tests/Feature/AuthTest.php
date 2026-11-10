<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_erp_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Empat langkah menyiapkan aplikasi')
            ->assertDontSee('/register');
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('boms.index'))->assertRedirect(route('login'));
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_admin_can_create_users_with_any_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'ERP User',
                'email' => 'erp-user@example.com',
                'role' => 'admin',
                'password' => 'Sufficient-pass-123',
                'password_confirmation' => 'Sufficient-pass-123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'erp-user@example.com', 'role' => 'admin']);
        $createdUser = User::query()->where('email', 'erp-user@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Sufficient-pass-123', $createdUser->password));
    }

    public function test_non_admin_cannot_create_users(): void
    {
        $user = User::factory()->create(['role' => 'sales']);

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Unauthorized User',
                'email' => 'unauthorized@example.com',
                'role' => 'admin',
                'password' => 'Sufficient-pass-123',
                'password_confirmation' => 'Sufficient-pass-123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.com']);
    }

    public function test_roles_restrict_modules_and_admin_can_manage_roles(): void
    {
        $salesUser = User::factory()->create(['role' => 'sales']);

        $this->actingAs($salesUser)
            ->get(route('sales-orders.index'))
            ->assertOk();
        $this->get(route('stock.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();

        $financeUser = User::factory()->create(['role' => 'finance']);
        $this->actingAs($financeUser)
            ->get(route('invoices.index'))
            ->assertOk();
        $this->get(route('deliveries.index'))->assertOk();
        $this->get(route('sales-orders.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();
        $this->put(route('users.role.update', $salesUser), ['role' => 'finance'])
            ->assertRedirect(route('users.index'));
        $this->assertSame('finance', $salesUser->fresh()->role);
        $this->put(route('users.role.update', $salesUser), ['role' => 'admin'])
            ->assertRedirect(route('users.index'));
        $this->assertSame('admin', $salesUser->fresh()->role);

        $this->put(route('users.role.update', $admin), ['role' => 'sales'])
            ->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);

        $promotable = User::factory()->create();
        $this->artisan('user:make-admin', ['email' => $promotable->email])
            ->assertSuccessful();
        $this->assertSame('admin', $promotable->fresh()->role);
    }

    public function test_admin_can_customize_and_reset_user_menu_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'sales']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Hak Akses Menu')
            ->assertSee('Daftarkan Pengguna')
            ->assertSee('Simpan hak akses');

        $this->actingAs($admin)
            ->put(route('users.access.update', $user), [
                'permissions' => ['stock', 'finance'],
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame(['stock', 'finance'], $user->fresh()->permissions);

        $this->actingAs($user->fresh())
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Stok');
        $this->get(route('sales-orders.index'))->assertForbidden();
        $this->get(route('invoices.index'))->assertOk();
        $this->get(route('master-data.index', 'items'))->assertForbidden();
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stok')
            ->assertDontSee('Sales Order');

        $productionUser = User::factory()->create(['role' => 'production']);
        $this->actingAs($admin)
            ->put(route('users.access.update', $productionUser), [
                'permissions' => ['master-data'],
            ])
            ->assertRedirect(route('users.index'));
        $this->actingAs($productionUser->fresh())
            ->get(route('master-data.index', 'items'))
            ->assertOk();
        $this->get(route('master-data.index', 'chart-of-accounts'))->assertForbidden();

        $this->actingAs($admin)
            ->put(route('users.access.update', $user), ['use_role_defaults' => '1'])
            ->assertRedirect(route('users.index'));

        $this->assertNull($user->fresh()->permissions);
        $this->actingAs($user->fresh())
            ->get(route('sales-orders.index'))
            ->assertOk();
        $this->get(route('stock.index'))->assertForbidden();
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Sufficient-pass-123'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Sufficient-pass-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_invalid_login_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_reset_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-sufficient-pass-123',
            'password_confirmation' => 'New-sufficient-pass-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New-sufficient-pass-123', $user->fresh()->password));
    }
}
