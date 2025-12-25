<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Suppliers\DigiflazzSupplierService;

class SyncDigiflazzProducts extends Command
{
    protected $signature = 'digiflazz:sync';
    protected $description = 'Sync product price-list from Digiflazz';

    public function handle(DigiflazzSupplierService $services)
    {
        $this->info('Syncing Digiflazz products...');
        $res = $services->syncProducts();
        $this->info('Result: ' . json_encode($res));
        $this->info('Auto pricing applied.');
    }
}
