<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentGateway::create([
            'name' => 'Duitku',
            'code' => 'duitku',
            'api_key' => config('services.duitku.api_key'),
            'api_secret' => config('services.duitku.merchant_code'),
            'callback_url' => config('services.duitku.callback_url'),
            'redirect_url' => config('services.duitku.return_url'),
            'is_active' => true,
        ]);
    }
}
