<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $moba = Category::where('slug', 'moba')->first();
        $battleRoyale = Category::where('slug', 'battle-royale')->first();
        $rpg = Category::where('slug', 'rpg')->first();
        $casual = Category::where('slug', 'casual')->first();

        $games = [
            [
                'category_id' => $moba->id,
                'name' => 'Mobile Legends',
                'slug' => 'mobile-legends',
                'provider' => 'Moonton',
                'description' => 'Top up Mobile Legends Diamond tercepat dan termurah. Proses otomatis 24/7.',
                'is_active' => true,
            ],
            [
                'category_id' => $battleRoyale->id,
                'name' => 'Free Fire',
                'slug' => 'free-fire',
                'provider' => 'Garena',
                'description' => 'Beli Diamond Free Fire murah dan cepat. Aman dan terpercaya.',
                'is_active' => true,
            ],
            [
                'category_id' => $battleRoyale->id,
                'name' => 'PUBG Mobile',
                'slug' => 'pubg-mobile',
                'provider' => 'Tencent',
                'description' => 'Top up UC PUBG Mobile instant. Harga terbaik dijamin.',
                'is_active' => true,
            ],
            [
                'category_id' => $rpg->id,
                'name' => 'Genshin Impact',
                'slug' => 'genshin-impact',
                'provider' => 'HoYoverse',
                'description' => 'Top up Genesis Crystal Genshin Impact cepat dan aman.',
                'is_active' => true,
            ],
            [
                'category_id' => $battleRoyale->id,
                'name' => 'Valorant',
                'slug' => 'valorant',
                'provider' => 'Riot Games',
                'description' => 'Beli Valorant Points (VP) murah. Proses cepat tanpa ribet.',
                'is_active' => true,
            ],
            [
                'category_id' => $battleRoyale->id,
                'name' => 'Call of Duty Mobile',
                'slug' => 'call-of-duty-mobile',
                'provider' => 'Activision',
                'description' => 'Top up CP COD Mobile instant 24 jam. Harga promo.',
                'is_active' => true,
            ],
            [
                'category_id' => $moba->id,
                'name' => 'Arena of Valor',
                'slug' => 'arena-of-valor',
                'provider' => 'Tencent',
                'description' => 'Beli Voucher AOV termurah. Proses otomatis dan cepat.',
                'is_active' => true,
            ],
            [
                'category_id' => $rpg->id,
                'name' => 'Honkai Star Rail',
                'slug' => 'honkai-star-rail',
                'provider' => 'HoYoverse',
                'description' => 'Top up Oneiric Shard Honkai Star Rail mudah dan aman.',
                'is_active' => true,
            ],
            [
                'category_id' => $moba->id,
                'name' => 'League of Legends Wild Rift',
                'slug' => 'league-of-legends-wild-rift',
                'provider' => 'Riot Games',
                'description' => 'Beli Wild Core LOL Wild Rift murah. Proses instant.',
                'is_active' => true,
            ],
            [
                'category_id' => $casual->id,
                'name' => 'Stumble Guys',
                'slug' => 'stumble-guys',
                'provider' => 'Scopely',
                'description' => 'Top up Gems Stumble Guys cepat dan terpercaya.',
                'is_active' => true,
            ],
        ];

        foreach ($games as $game) {
            Game::create($game);
        }
    }
}
