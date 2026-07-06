<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@codflow.test'],
            [
                'name' => 'Admin CODFlow',
                'password' => Hash::make('password'),
                'phone' => '0600000001',
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
