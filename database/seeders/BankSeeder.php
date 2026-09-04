<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([
            ['code' => 'BTN', 'name' => 'BTN'],
            ['code' => 'BTNS', 'name' => 'BTN Syariah'],
            ['code' => 'BRI', 'name' => 'BRI'],
            ['code' => 'BJTG', 'name' => 'Bank Jateng'],
            ['code' => 'BNI', 'name' => 'BNI'],
        ] as $bank) {
            Bank::firstOrCreate(['code' => $bank['code']], [
                'name' => $bank['name'],
                'is_active' => true,
            ]);
        }
    }
}
