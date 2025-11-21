<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'name' => 'Digiflazz',
            'code' => 'digiflazz',
            'api_url' => config('services.digiflazz.url', 'https://api.digiflazz.com/v1'),
            'api_key' => config('services.digiflazz.api_key'),
            'secret' => config('services.digiflazz.webhook_secret'),
            'is_active' => true,
        ]);
    }
}
