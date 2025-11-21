<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\FieldForm;
use Illuminate\Database\Seeder;

class FieldFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mobile Legends - User ID + Zone ID
        $mlGame = Game::where('slug', 'mobile-legends')->first();
        if ($mlGame) {
            FieldForm::create([
                'game_id' => $mlGame->id,
                'label' => 'User ID',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);

            FieldForm::create([
                'game_id' => $mlGame->id,
                'label' => 'Zone ID',
                'name' => 'zone_id',
                'type' => 'number',
                'is_required' => true,
            ]);
        }

        // Free Fire - User ID only
        $ffGame = Game::where('slug', 'free-fire')->first();
        if ($ffGame) {
            FieldForm::create([
                'game_id' => $ffGame->id,
                'label' => 'User ID',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);
        }

        // PUBG Mobile - User ID + Character
        $pubgGame = Game::where('slug', 'pubg-mobile')->first();
        if ($pubgGame) {
            FieldForm::create([
                'game_id' => $pubgGame->id,
                'label' => 'User ID',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);
        }

        // Genshin Impact - User ID + Server
        $genshinGame = Game::where('slug', 'genshin-impact')->first();
        if ($genshinGame) {
            FieldForm::create([
                'game_id' => $genshinGame->id,
                'label' => 'User ID (UID)',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);

            FieldForm::create([
                'game_id' => $genshinGame->id,
                'label' => 'Server',
                'name' => 'server',
                'type' => 'text',
                'is_required' => true,
            ]);
        }

        // Valorant - Riot ID + Tagline
        $valorantGame = Game::where('slug', 'valorant')->first();
        if ($valorantGame) {
            FieldForm::create([
                'game_id' => $valorantGame->id,
                'label' => 'Riot ID',
                'name' => 'riot_id',
                'type' => 'text',
                'is_required' => true,
            ]);

            FieldForm::create([
                'game_id' => $valorantGame->id,
                'label' => 'Tagline (contoh: #SEA1)',
                'name' => 'tagline',
                'type' => 'text',
                'is_required' => true,
            ]);
        }

        // Call of Duty Mobile - User ID + Zone
        $codGame = Game::where('slug', 'call-of-duty-mobile')->first();
        if ($codGame) {
            FieldForm::create([
                'game_id' => $codGame->id,
                'label' => 'User ID',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);

            FieldForm::create([
                'game_id' => $codGame->id,
                'label' => 'Zone ID',
                'name' => 'zone_id',
                'type' => 'number',
                'is_required' => true,
            ]);
        }

        // Arena of Valor - User ID only
        $aovGame = Game::where('slug', 'arena-of-valor')->first();
        if ($aovGame) {
            FieldForm::create([
                'game_id' => $aovGame->id,
                'label' => 'User ID',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);
        }

        // Honkai Star Rail - User ID + Server
        $honkaiGame = Game::where('slug', 'honkai-star-rail')->first();
        if ($honkaiGame) {
            FieldForm::create([
                'game_id' => $honkaiGame->id,
                'label' => 'User ID (UID)',
                'name' => 'user_id',
                'type' => 'number',
                'is_required' => true,
            ]);

            FieldForm::create([
                'game_id' => $honkaiGame->id,
                'label' => 'Server',
                'name' => 'server',
                'type' => 'text',
                'is_required' => true,
            ]);
        }

        // League of Legends Wild Rift - Riot ID + Tagline
        $lolwrGame = Game::where('slug', 'league-of-legends-wild-rift')->first();
        if ($lolwrGame) {
            FieldForm::create([
                'game_id' => $lolwrGame->id,
                'label' => 'Riot ID',
                'name' => 'riot_id',
                'type' => 'text',
                'is_required' => true,
            ]);

            FieldForm::create([
                'game_id' => $lolwrGame->id,
                'label' => 'Tagline',
                'name' => 'tagline',
                'type' => 'text',
                'is_required' => true,
            ]);
        }

        // Stumble Guys - User ID only
        $stumbleGame = Game::where('slug', 'stumble-guys')->first();
        if ($stumbleGame) {
            FieldForm::create([
                'game_id' => $stumbleGame->id,
                'label' => 'User ID',
                'name' => 'user_id',
                'type' => 'text',
                'is_required' => true,
            ]);
        }
    }
}
