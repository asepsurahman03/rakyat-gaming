<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FlazzSell;
use App\Models\FlazzSellProduct;
use App\Models\Product;
use Carbon\Carbon;

class FlazzSellSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Event 1: Flash Sale Akhir Bulan
        $event1 = FlazzSell::create([
            'name' => 'Flash Sale Akhir Bulan',
            'description' => 'Diskon besar-besaran untuk game favorit kamu! Buruan serbu sebelum kehabisan!',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addDays(7),
            'is_active' => true,
        ]);

        // Add products to Event 1 with different discounts and quotas
        $mlProducts = Product::whereHas('game', function($q) {
            $q->where('name', 'Mobile Legends');
        })->limit(5)->get();

        foreach ($mlProducts as $index => $product) {
            FlazzSellProduct::create([
                'flazz_sell_id' => $event1->id,
                'product_id' => $product->id,
                'discount_percentage' => 15 + ($index * 5), // 15%, 20%, 25%, 30%, 35%
                'quota' => 100 - ($index * 10), // 100, 90, 80, 70, 60
                'sold_count' => rand(0, 20), // Random sold count
            ]);
        }

        // Event 2: Weekend Sale
        $event2 = FlazzSell::create([
            'name' => 'Weekend Sale - Special Promo',
            'description' => 'Promo spesial akhir pekan! Disc up to 40% untuk produk pilihan!',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addDays(3),
            'is_active' => true,
        ]);

        // Add Free Fire products to Event 2
        $ffProducts = Product::whereHas('game', function($q) {
            $q->where('name', 'Free Fire');
        })->limit(4)->get();

        foreach ($ffProducts as $index => $product) {
            FlazzSellProduct::create([
                'flazz_sell_id' => $event2->id,
                'product_id' => $product->id,
                'discount_percentage' => 20 + ($index * 5), // 20%, 25%, 30%, 35%
                'quota' => 50, // Fixed quota for all
                'sold_count' => rand(0, 15),
            ]);
        }

        // Event 3: PUBG Special Sale
        $event3 = FlazzSell::create([
            'name' => 'PUBG Mega Sale',
            'description' => 'Diskon gila-gilaan untuk UC PUBG! Stock terbatas, siapa cepat dia dapat!',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addDays(5),
            'is_active' => true,
        ]);

        // Add PUBG products to Event 3
        $pubgProducts = Product::whereHas('game', function($q) {
            $q->where('name', 'PUBG Mobile');
        })->limit(6)->get();

        foreach ($pubgProducts as $index => $product) {
            FlazzSellProduct::create([
                'flazz_sell_id' => $event3->id,
                'product_id' => $product->id,
                'discount_percentage' => 10 + ($index * 3), // 10%, 13%, 16%, 19%, 22%, 25%
                'quota' => null, // Unlimited quota
                'sold_count' => rand(0, 30),
            ]);
        }

        // Event 4: Genshin Impact Anniversary Sale
        $event4 = FlazzSell::create([
            'name' => 'Genshin Impact Anniversary',
            'description' => 'Rayakan anniversary dengan diskon Crystal! Limited time only!',
            'start_at' => Carbon::now(),
            'end_at' => Carbon::now()->addDays(10),
            'is_active' => true,
        ]);

        // Add Genshin products to Event 4
        $genshinProducts = Product::whereHas('game', function($q) {
            $q->where('name', 'Genshin Impact');
        })->limit(5)->get();

        foreach ($genshinProducts as $index => $product) {
            FlazzSellProduct::create([
                'flazz_sell_id' => $event4->id,
                'product_id' => $product->id,
                'discount_percentage' => 12 + ($index * 4), // 12%, 16%, 20%, 24%, 28%
                'quota' => 75,
                'sold_count' => rand(0, 25),
            ]);
        }

        // Event 5: Mix Games Flash Sale (Inactive - Scheduled for future)
        $event5 = FlazzSell::create([
            'name' => 'Mix Games Flash Sale',
            'description' => 'Event spesial dengan berbagai game populer! Coming soon!',
            'start_at' => Carbon::now()->addDays(14),
            'end_at' => Carbon::now()->addDays(21),
            'is_active' => false, // Not active yet
        ]);

        // Add mix of products from different games
        $aovProducts = Product::whereHas('game', function($q) {
            $q->where('name', 'Arena of Valor');
        })->limit(3)->get();

        foreach ($aovProducts as $index => $product) {
            FlazzSellProduct::create([
                'flazz_sell_id' => $event5->id,
                'product_id' => $product->id,
                'discount_percentage' => 25,
                'quota' => 100,
                'sold_count' => 0,
            ]);
        }
    }
}
