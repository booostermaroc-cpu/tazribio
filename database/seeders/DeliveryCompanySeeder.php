<?php

namespace Database\Seeders;

use App\Models\DeliveryCompany;
use Illuminate\Database\Seeder;

class DeliveryCompanySeeder extends Seeder
{
    public function run(): void
    {
        DeliveryCompany::query()->updateOrCreate(
            ['name' => 'Amana Express'],
            [
                'phone' => '0522000000',
                'api_url' => 'https://api.amana.example/track',
                'api_token' => 'sample-token',
                'is_active' => true,
            ]
        );
    }
}
