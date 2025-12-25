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
        // Digiflazz
        Supplier::updateOrCreate(
            ['code' => 'digiflazz'],
            [
                'username' => 'hihuxeoQ47Gg',
                'name' => 'Digiflazz',
                'api_url' => config('services.digiflazz.url', 'https://api.digiflazz.com/v1'),
                'api_key' => config('services.digiflazz.api_key'),
                'secret' => config('services.digiflazz.webhook_secret'),
                'is_active' => true,
            ]
        );

        // OnoyStore
        Supplier::updateOrCreate(
            ['code' => 'onoystore'],
            [
                'username' => null,
                'name' => 'OnoyStore',
                'api_url' => config('services.onoystore.url', 'https://api.onoystore.com'),
                'api_key' => config('services.onoystore.api_key'),
                'secret' => config('services.onoystore.secret'),
                'is_active' => false, // Set to false by default until configured
            ]
        );

        // ByxStore
        Supplier::updateOrCreate(
            ['code' => 'byxstore'],
            [
                'username' => null,
                'name' => 'ByxStore',
                'api_url' => config('services.byxstore.url', 'https://api.byxstore.com'),
                'api_key' => config('services.byxstore.api_key'),
                'secret' => config('services.byxstore.secret'),
                'is_active' => false, // Set to false by default until configured
            ]
        );
    }
}
