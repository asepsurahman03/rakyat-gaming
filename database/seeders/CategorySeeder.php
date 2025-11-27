<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'MOBA',
                'slug' => 'moba',
                'icon' => '⚔️',
                'description' => 'Multiplayer Online Battle Arena - Game strategi tim seperti Mobile Legends, Arena of Valor',
                'sort_order' => 1,
            ],
            [
                'name' => 'Battle Royale',
                'slug' => 'battle-royale',
                'icon' => '🎯',
                'description' => 'Game survival battle royale seperti PUBG Mobile, Free Fire, Call of Duty Mobile',
                'sort_order' => 2,
            ],
            [
                'name' => 'RPG',
                'slug' => 'rpg',
                'icon' => '🗡️',
                'description' => 'Role Playing Game - Game petualangan dan eksplorasi seperti Genshin Impact, Honkai Star Rail',
                'sort_order' => 3,
            ],
            [
                'name' => 'MMO',
                'slug' => 'mmo',
                'icon' => '🌍',
                'description' => 'Massively Multiplayer Online - Game dengan dunia besar dan banyak pemain',
                'sort_order' => 4,
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'icon' => '⚽',
                'description' => 'Game olahraga seperti FIFA Mobile, eFootball, NBA 2K Mobile',
                'sort_order' => 5,
            ],
            [
                'name' => 'Card Game',
                'slug' => 'card-game',
                'icon' => '🃏',
                'description' => 'Game kartu strategis seperti Yu-Gi-Oh!, Hearthstone',
                'sort_order' => 6,
            ],
            [
                'name' => 'Casual',
                'slug' => 'casual',
                'icon' => '🎮',
                'description' => 'Game santai dan mudah dimainkan untuk semua umur',
                'sort_order' => 7,
            ],
            [
                'name' => 'Simulation',
                'slug' => 'simulation',
                'icon' => '🏗️',
                'description' => 'Game simulasi seperti The Sims Mobile, SimCity BuildIt',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
