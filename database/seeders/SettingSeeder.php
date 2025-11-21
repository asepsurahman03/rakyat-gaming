<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => 'Warung Gaming',
            'site_description' => 'Toko dan rental game terbaik',
            'site_logo' => '/images/logo.png',
            'contact_email' => 'info@warunggaming.local',
            'contact_phone' => '+62 812-3456-7890',
            'address' => 'Jl. Contoh No.1, Jakarta',
            'facebook' => 'https://facebook.com/warunggaming',
            'instagram' => 'https://instagram.com/warunggaming',
            'twitter' => 'https://twitter.com/warunggaming',
        ];

        foreach ($settings as $key => $value) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
