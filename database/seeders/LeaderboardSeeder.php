<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Leaderboard;
use Carbon\Carbon;

class LeaderboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Weekly Leaderboard Data
        $weeklyData = [
            ['name' => 'Budi Santoso', 'totalSpent' => 2450000, 'txCount' => 18, 'lastItem' => 'Mobile Legends 5000 Diamond', 'rank' => 1],
            ['name' => 'Rina Wijaya', 'totalSpent' => 1980000, 'txCount' => 15, 'lastItem' => 'Free Fire 2000 Diamond', 'rank' => 2],
            ['name' => 'Ahmad Fauzi', 'totalSpent' => 1750000, 'txCount' => 12, 'lastItem' => 'PUBG UC 3600', 'rank' => 3],
            ['name' => 'Siti Nurhaliza', 'totalSpent' => 1420000, 'txCount' => 11, 'lastItem' => 'Genshin Impact Welkin', 'rank' => 4],
            ['name' => 'Denny Prakoso', 'totalSpent' => 1150000, 'txCount' => 9, 'lastItem' => 'Valorant VP 2400', 'rank' => 5],
            ['name' => 'Maya Putri', 'totalSpent' => 985000, 'txCount' => 8, 'lastItem' => 'Mobile Legends Weekly Pass', 'rank' => 6],
            ['name' => 'Ricky Harun', 'totalSpent' => 870000, 'txCount' => 7, 'lastItem' => 'Honkai Star Rail Pass', 'rank' => 7],
            ['name' => 'Dewi Lestari', 'totalSpent' => 750000, 'txCount' => 6, 'lastItem' => 'Free Fire Bundle Elite', 'rank' => 8],
            ['name' => 'Eko Wijaya', 'totalSpent' => 620000, 'txCount' => 5, 'lastItem' => 'PUBG Royale Pass', 'rank' => 9],
            ['name' => 'Lisa Andriani', 'totalSpent' => 540000, 'txCount' => 4, 'lastItem' => 'Mobile Legends 1000 Diamond', 'rank' => 10],
        ];

        foreach ($weeklyData as $data) {
            Leaderboard::create([
                'period' => 'week',
                'name' => $data['name'],
                'total_spent' => $data['totalSpent'],
                'transaction_count' => $data['txCount'],
                'last_item' => $data['lastItem'],
                'last_transaction_time' => Carbon::now()->subMinutes(rand(5, 300)),
                'is_active' => true,
                'rank' => $data['rank'],
            ]);
        }

        // Monthly Leaderboard Data
        $monthlyData = [
            ['name' => 'Andi Wijaya', 'totalSpent' => 8950000, 'txCount' => 64, 'lastItem' => 'Mobile Legends 5000 Diamond', 'rank' => 1],
            ['name' => 'Putri Rahayu', 'totalSpent' => 7320000, 'txCount' => 52, 'lastItem' => 'Free Fire Monthly Pass', 'rank' => 2],
            ['name' => 'Bambang Susilo', 'totalSpent' => 6480000, 'txCount' => 47, 'lastItem' => 'Genshin Impact Crystal', 'rank' => 3],
            ['name' => 'Wulan Dari', 'totalSpent' => 5850000, 'txCount' => 41, 'lastItem' => 'PUBG UC 8100', 'rank' => 4],
            ['name' => 'Hendra Gunawan', 'totalSpent' => 4920000, 'txCount' => 35, 'lastItem' => 'Valorant VP 5000', 'rank' => 5],
            ['name' => 'Sari Indah', 'totalSpent' => 4350000, 'txCount' => 32, 'lastItem' => 'Mobile Legends Starlight', 'rank' => 6],
            ['name' => 'Toni Hermawan', 'totalSpent' => 3780000, 'txCount' => 28, 'lastItem' => 'Free Fire 5000 Diamond', 'rank' => 7],
            ['name' => 'Mega Sari', 'totalSpent' => 3240000, 'txCount' => 24, 'lastItem' => 'Honkai Impact Crystal', 'rank' => 8],
            ['name' => 'Dodi Setiawan', 'totalSpent' => 2850000, 'txCount' => 21, 'lastItem' => 'PUBG Royale Pass', 'rank' => 9],
            ['name' => 'Nina Marlina', 'totalSpent' => 2460000, 'txCount' => 18, 'lastItem' => 'Mobile Legends 3000 Diamond', 'rank' => 10],
        ];

        foreach ($monthlyData as $data) {
            Leaderboard::create([
                'period' => 'month',
                'name' => $data['name'],
                'total_spent' => $data['totalSpent'],
                'transaction_count' => $data['txCount'],
                'last_item' => $data['lastItem'],
                'last_transaction_time' => Carbon::now()->subMinutes(rand(5, 300)),
                'is_active' => true,
                'rank' => $data['rank'],
            ]);
        }

        // All Time Leaderboard Data
        $allTimeData = [
            ['name' => 'Agus Setiawan', 'totalSpent' => 45750000, 'txCount' => 287, 'lastItem' => 'Mobile Legends 10000 Diamond', 'rank' => 1],
            ['name' => 'Diana Kusuma', 'totalSpent' => 38920000, 'txCount' => 245, 'lastItem' => 'Free Fire Mega Bundle', 'rank' => 2],
            ['name' => 'Fadli Rahman', 'totalSpent' => 32450000, 'txCount' => 198, 'lastItem' => 'Genshin Impact Bundle', 'rank' => 3],
            ['name' => 'Indah Permata', 'totalSpent' => 28750000, 'txCount' => 176, 'lastItem' => 'PUBG UC 16200', 'rank' => 4],
            ['name' => 'Yudi Pratama', 'totalSpent' => 24680000, 'txCount' => 152, 'lastItem' => 'Valorant Premium Bundle', 'rank' => 5],
            ['name' => 'Ratna Sari', 'totalSpent' => 21350000, 'txCount' => 138, 'lastItem' => 'Mobile Legends Premium', 'rank' => 6],
            ['name' => 'Irfan Hakim', 'totalSpent' => 18920000, 'txCount' => 124, 'lastItem' => 'Free Fire Elite Pass', 'rank' => 7],
            ['name' => 'Sinta Dewi', 'totalSpent' => 16450000, 'txCount' => 109, 'lastItem' => 'Honkai Premium Pass', 'rank' => 8],
            ['name' => 'Wahyu Nugroho', 'totalSpent' => 14780000, 'txCount' => 98, 'lastItem' => 'PUBG Season Pass', 'rank' => 9],
            ['name' => 'Lilis Suryani', 'totalSpent' => 12950000, 'txCount' => 87, 'lastItem' => 'Mobile Legends 5000 Diamond', 'rank' => 10],
        ];

        foreach ($allTimeData as $data) {
            Leaderboard::create([
                'period' => 'all',
                'name' => $data['name'],
                'total_spent' => $data['totalSpent'],
                'transaction_count' => $data['txCount'],
                'last_item' => $data['lastItem'],
                'last_transaction_time' => Carbon::now()->subMinutes(rand(5, 300)),
                'is_active' => true,
                'rank' => $data['rank'],
            ]);
        }
    }
}

