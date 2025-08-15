<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Util;

class Billable
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!Util::getShopifyConfig('billing_enabled') === true) {
                return $next($request);
            }

            if (!Util::useNativeAppBridge() && !$request->ajax()) {
                return $next($request);
            }

            /** @var $shop IShopModel */
            $shop = auth()->user();
            if (!$shop->plan && !$shop->isFreemium() && !$shop->isGrandfathered() && $request->ajax()) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                $shopDomain = $shop->getDomain()->toNative();

                $host = $request->get('host') ??
                    base64_encode('admin.shopify.com/store/' . str_replace('.myshopify.com', '', $shopDomain));

                $planId = $shop->draft_plan_id ?? null;

                $redirectUrl = route(
                    Util::getShopifyConfig('route_names.billing'),
                    array_merge($request->input(), [
                        'shop' => $shopDomain,
                        'host' => $host,
                        'locale' => $request->get('locale'),
                        'plan' => $planId,
                    ])
                );

                return response()->json([
                    'forceRedirectUrl' => $redirectUrl
                ], 403);
            }
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }

        // Move on, everything's fine
        return $next($request);
    }
}
