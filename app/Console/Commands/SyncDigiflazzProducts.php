<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DigiflazzService;

class SyncDigiflazzProducts extends Command
{
    protected $signature = 'digiflazz:sync';
    protected $description = 'Sync product price-list from Digiflazz';

    public function handle(DigiflazzService $services)
    {
        $this->info('Syncing Digiflazz products...');
        $res = $services->syncPriceList();
        $this->info('Result: ' . json_encode($res));
        // Apply auto pricing after sync
        $services->applyAutoPricing();
        $this->info('Auto pricing applied.');
    }
}
