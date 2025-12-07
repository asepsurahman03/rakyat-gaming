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
            'site_name' => 'Rakyat Gaming',
            'site_description' => 'Toko dan rental game terbaik',
            'site_logo' => '/images/logo.png',
            'contact_email' => 'officialrakyatgaming@gmail.com',
            'contact_phone' => '+62 813-1543-5065‬',
            'address' => 'Jakarta, Indonesia',
            'facebook' => 'https://facebook.com/rakyatgaming',
            'instagram' => 'https://instagram.com/rakyatgaming',
            'twitter' => 'https://twitter.com/rakyatgaming',
        ];

        foreach ($settings as $key => $value) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
