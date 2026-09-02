<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseZeroFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_success(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Database Oasis');
    }

    public function test_active_user_can_log_in_and_log_out(): void
    {
        $this->seed();
        $user = User::factory()->create([
            'password' => Hash::make('secret-password'),
        ]);
        $user->assignRole(UserRole::SuperAdmin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'secret-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->seed();
        $user = User::factory()->inactive()->create([
            'password' => Hash::make('secret-password'),
        ]);
        $user->assignRole(UserRole::SuperAdmin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'secret-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_inactive_authenticated_user_is_forbidden_from_admin_panel(): void
    {
        $this->seed();
        $user = User::factory()->inactive()->create();
        $user->assignRole(UserRole::SuperAdmin);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_active_roleless_user_is_forbidden_from_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_users_receive_ulid_primary_keys(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', $user->id);
    }

    public function test_database_seeder_creates_super_admin_role(): void
    {
        $this->seed();

        $this->assertDatabaseHas('roles', [
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole(UserRole::SuperAdmin);

        $this->assertTrue(Gate::forUser($user)->allows('unconfigured-permission'));
    }
}
