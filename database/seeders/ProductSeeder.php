<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = Game::all();

        foreach ($games as $game) {
            // Create products based on game type
            switch ($game->name) {
                case 'Mobile Legends':
                    $this->createMLProducts($game);
                    break;
                case 'Free Fire':
                    $this->createFFProducts($game);
                    break;
                case 'PUBG Mobile':
                    $this->createPUBGProducts($game);
                    break;
                case 'Genshin Impact':
                    $this->createGenshinProducts($game);
                    break;
                case 'Arena of Valor':
                    $this->createAOVProducts($game);
                    break;
                default:
                    $this->createGenericProducts($game);
                    break;
            }
        }
    }

    private function createMLProducts(Game $game)
    {
        $products = [
            ['name' => '10 Diamonds', 'price' => 3000],
            ['name' => '19 Diamonds', 'price' => 5500],
            ['name' => '28 Diamonds', 'price' => 8000],
            ['name' => '36 Diamonds', 'price' => 10000],
            ['name' => '44 Diamonds', 'price' => 12000],
            ['name' => '59 Diamonds', 'price' => 16000],
            ['name' => '74 Diamonds', 'price' => 20000],
            ['name' => '85 Diamonds', 'price' => 23000],
            ['name' => '170 Diamonds', 'price' => 45000],
            ['name' => '185 Diamonds', 'price' => 48000],
            ['name' => '222 Diamonds', 'price' => 58000],
            ['name' => '296 Diamonds', 'price' => 75000],
            ['name' => '370 Diamonds', 'price' => 95000],
            ['name' => '408 Diamonds', 'price' => 105000],
            ['name' => '568 Diamonds', 'price' => 145000],
            ['name' => '875 Diamonds', 'price' => 225000],
            ['name' => '1159 Diamonds', 'price' => 295000],
            ['name' => '2010 Diamonds', 'price' => 510000],
            ['name' => '4830 Diamonds', 'price' => 1200000],
            ['name' => 'Weekly Diamond Pass', 'price' => 28000],
            ['name' => 'Twilight Pass', 'price' => 145000],
        ];

        $this->createProducts($game, $products);
    }

    private function createFFProducts(Game $game)
    {
        $products = [
            ['name' => '50 Diamonds', 'price' => 7000],
            ['name' => '70 Diamonds', 'price' => 10000],
            ['name' => '100 Diamonds', 'price' => 14000],
            ['name' => '140 Diamonds', 'price' => 19000],
            ['name' => '210 Diamonds', 'price' => 28000],
            ['name' => '355 Diamonds', 'price' => 47000],
            ['name' => '425 Diamonds', 'price' => 57000],
            ['name' => '720 Diamonds', 'price' => 95000],
            ['name' => '1450 Diamonds', 'price' => 190000],
            ['name' => '2180 Diamonds', 'price' => 285000],
            ['name' => '3640 Diamonds', 'price' => 475000],
            ['name' => '7290 Diamonds', 'price' => 950000],
            ['name' => 'Member Mingguan', 'price' => 27000],
            ['name' => 'Member Bulanan', 'price' => 135000],
            ['name' => 'Level Up Pass', 'price' => 95000],
        ];

        $this->createProducts($game, $products);
    }

    private function createPUBGProducts(Game $game)
    {
        $products = [
            ['name' => '60 UC', 'price' => 15000],
            ['name' => '120 UC', 'price' => 28000],
            ['name' => '180 UC', 'price' => 42000],
            ['name' => '300 UC', 'price' => 70000],
            ['name' => '600 UC', 'price' => 135000],
            ['name' => '660 UC', 'price' => 145000],
            ['name' => '1500 UC', 'price' => 340000],
            ['name' => '1800 UC', 'price' => 405000],
            ['name' => '3000 UC', 'price' => 675000],
            ['name' => '6000 UC', 'price' => 1350000],
            ['name' => 'Royale Pass', 'price' => 135000],
            ['name' => 'Royale Pass Plus', 'price' => 270000],
        ];

        $this->createProducts($game, $products);
    }

    private function createGenshinProducts(Game $game)
    {
        $products = [
            ['name' => '60 Genesis Crystals', 'price' => 16000],
            ['name' => '300 Genesis Crystals', 'price' => 75000],
            ['name' => '980 Genesis Crystals', 'price' => 240000],
            ['name' => '1980 Genesis Crystals', 'price' => 475000],
            ['name' => '3280 Genesis Crystals', 'price' => 780000],
            ['name' => '6480 Genesis Crystals', 'price' => 1550000],
            ['name' => 'Blessing of the Welkin Moon', 'price' => 65000],
            ['name' => 'Battle Pass (Gnostic Hymn)', 'price' => 145000],
            ['name' => 'Battle Pass (Gnostic Chorus)', 'price' => 285000],
        ];

        $this->createProducts($game, $products);
    }

    private function createAOVProducts(Game $game)
    {
        $products = [
            ['name' => '50 Vouchers', 'price' => 12000],
            ['name' => '100 Vouchers', 'price' => 23000],
            ['name' => '250 Vouchers', 'price' => 56000],
            ['name' => '500 Vouchers', 'price' => 110000],
            ['name' => '1000 Vouchers', 'price' => 220000],
            ['name' => '2000 Vouchers', 'price' => 440000],
            ['name' => '5000 Vouchers', 'price' => 1100000],
        ];

        $this->createProducts($game, $products);
    }

    private function createGenericProducts(Game $game)
    {
        $products = [
            ['name' => 'Paket Kecil', 'price' => 10000],
            ['name' => 'Paket Sedang', 'price' => 25000],
            ['name' => 'Paket Besar', 'price' => 50000],
            ['name' => 'Paket Premium', 'price' => 100000],
            ['name' => 'Paket VIP', 'price' => 250000],
        ];

        $this->createProducts($game, $products);
    }

    private function createProducts(Game $game, array $products)
    {
        foreach ($products as $product) {
            Product::create([
                'game_id' => $game->id,
                'name' => $product['name'],
                'selling_price' => $product['price'],
                'is_active' => true,
                'is_auto_price' => false,
                'margin' => 0,
                'description' => "Top up {$product['name']} untuk {$game->name}",
            ]);
        }
    }
}
