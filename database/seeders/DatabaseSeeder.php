<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->call([
            SettingSeeder::class,
            SupplierSeeder::class,
            PaymentGatewaySeeder::class,
            // CategorySeeder::class,
            // GameSeeder::class,
            // FieldFormSeeder::class,
            // ProductSeeder::class,
            // FlazzSellSeeder::class,
        ]);
    }
}
