<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ThemeInstaller;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Actions\CancelCurrentPlan;
use Osiset\ShopifyApp\Messaging\Events\AppUninstalledEvent;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Util;

class AppUninstalledJob extends \Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob
{
    public function handle(
        IShopCommand                                            $shopCommand,
        IShopQuery                                              $shopQuery,
        CancelCurrentPlan                                       $cancelCurrentPlanAction
    ): bool {
        $this->domain = ShopDomain::fromNative($this->domain);
        $shop = $shopQuery->getByDomain($this->domain);
        $shopId = $shop->getId();

        // Cleanup BEFORE token is deleted
        try {
            $shop->dualPricingLocales()->delete();

            \Log::info("✅ AppUninstalledJob: Snippet and config removed for {$shop->name}");
        } catch (\Throwable $e) {
            \Log::error("❌ AppUninstalledJob cleanup failed: " . $e->getMessage());
        }

        // Now proceed with the default behavior
        $cancelCurrentPlanAction($shopId);
        $shopCommand->clean($shopId);

        if (Util::getShopifyConfig('billing_freemium_enabled') === true) {
            $shopCommand->setAsFreemium($shopId);
        }

        $shopCommand->softDelete($shopId);

        event(new AppUninstalledEvent($shop));

        return true;
    }
}
