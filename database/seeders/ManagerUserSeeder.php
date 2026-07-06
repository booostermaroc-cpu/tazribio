<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ManagerUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'manager@codflow.test'],
            [
                'name' => 'Manager CODFlow',
                'password' => Hash::make('password'),
                'phone' => '0600000002',
                'role' => 'manager',
                'is_active' => true,
            ]
        );
    }
}
