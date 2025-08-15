<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ThemeInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RefreshStoreExpires extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-store-expires';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stores = User::whereNotNull('expires_at')
            ->where('expires_at', '<', now()->addDays(5)) // expires within 48h
            ->get();

        foreach ($stores as $shop) {
            try {
                if (Carbon::parse($shop->expires_at)->lte(now()->addDay())) {
                    $installer = new ThemeInstaller($shop);
                    $success = $installer->refreshExpireDate(); // contains new expiry date


                    if ($success) {
                        $shop->expires_at = now()->addDays(30);
                        $shop->save();
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Could not update {$shop->name}, probably uninstalled.");
            }
        }
    }
}
