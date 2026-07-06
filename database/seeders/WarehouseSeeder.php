<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::query()->updateOrCreate(
            ['name' => 'Entrepôt Principal Casablanca'],
            [
                'city' => 'Casablanca',
                'address' => 'Zone industrielle, Casablanca',
                'is_active' => true,
            ]
        );
    }
}
