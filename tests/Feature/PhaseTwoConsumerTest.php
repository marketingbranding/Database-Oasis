<?php

namespace Tests\Feature;

use App\Filament\Resources\Consumers\Pages\CreateConsumer;
use App\Models\Consumer;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoConsumerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function hqAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::HqAdmin);

        return $user;
    }

    public function test_valid_consumer_is_created_with_16_digit_nik(): void
    {
        $this->actingAs($this->hqAdmin());

        Livewire::test(CreateConsumer::class)
            ->fillForm([
                'nik' => '3325010101990001',
                'name' => 'Sri Wahyuni',
                'phone' => '081234567890',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('consumers', [
            'nik' => '3325010101990001',
            'name' => 'Sri Wahyuni',
        ]);
    }

    public function test_duplicate_nik_is_rejected_by_the_form(): void
    {
        Consumer::factory()->create(['nik' => '3325010101990001']);
        $this->actingAs($this->hqAdmin());

        Livewire::test(CreateConsumer::class)
            ->fillForm([
                'nik' => '3325010101990001',
                'name' => 'Budi Santoso',
            ])
            ->call('create')
            ->assertHasFormErrors(['nik']);

        $this->assertDatabaseCount('consumers', 1);
    }

    public function test_invalid_nik_length_is_rejected_by_the_form(): void
    {
        $this->actingAs($this->hqAdmin());

        Livewire::test(CreateConsumer::class)
            ->fillForm([
                'nik' => '332501010199001',
                'name' => 'Budi Santoso',
            ])
            ->call('create')
            ->assertHasFormErrors(['nik']);

        $this->assertDatabaseCount('consumers', 0);
    }

    public function test_duplicate_nik_is_blocked_by_the_database_constraint(): void
    {
        Consumer::factory()->create(['nik' => '3325010101990001']);

        $this->expectException(UniqueConstraintViolationException::class);

        Consumer::create([
            'nik' => '3325010101990001',
            'name' => 'Budi Santoso',
        ]);
    }

    public function test_branch_admin_cannot_create_consumers(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::BranchAdmin);

        $this->assertFalse($user->can('create', Consumer::class));

        $this->actingAs($user)
            ->get('/admin/consumers/create')
            ->assertForbidden();
    }
}
