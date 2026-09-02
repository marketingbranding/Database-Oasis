<?php

namespace Database\Seeders;

use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value);
        }
    }
}
