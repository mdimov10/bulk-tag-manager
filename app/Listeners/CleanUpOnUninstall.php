<?php

namespace App\Listeners;

use App\Services\ThemeInstaller;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Messaging\Events\AppUninstalledEvent;

class CleanUpOnUninstall
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppUninstalledEvent $event)
    {
//        $shop = auth()->user();
//
//        Log::info("[Event] AppUninstalledEvent for shop: {$shop->name}");
//
//        try {
//            $installer = new ThemeInstaller($shop);
//            $installer->remove();
//
//            $shop->dualPricingLocales()->delete();
//            $shop->delete();
//
//            Log::info("[Event] SUCCESS: AppUninstalledEvent for shop: {$shop->name}");
//        } catch (\Throwable $e) {
//            Log::error("[Event] Uninstall cleanup failed: " . $e->getMessage());
//        }
    }
}
