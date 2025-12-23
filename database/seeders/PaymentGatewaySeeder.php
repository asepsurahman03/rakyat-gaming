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
        // Legacy Duitku payment gateway
        // PaymentGateway::create([
        //     'name' => 'Duitku',
        //     'code' => 'duitku',
        //     'api_key' => config('services.duitku.api_key'),
        //     'api_secret' => config('services.duitku.merchant_code'),
        //     'callback_url' => config('services.duitku.callback_url'),
        //     'redirect_url' => config('services.duitku.return_url'),
        //     'is_active' => false, // Set inactive, use Ipaymu instead
        // ]);

        // Ipaymu payment gateway
        PaymentGateway::create([
            'name' => 'Ipaymu',
            'code' => 'ipaymu',
            'api_key' => config('services.ipaymu.api_key'),
            'api_secret' => config('services.ipaymu.va'),
            'callback_url' => config('services.ipaymu.callback_url'),
            'redirect_url' => config('services.ipaymu.redirect_url'),
            'is_active' => true,
        ]);
    }
}
