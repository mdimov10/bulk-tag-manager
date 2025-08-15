<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
            $redirectUrl = route(
                Util::getShopifyConfig('route_names.billing'),
                array_merge($request->input(), [
                    'shop' => $shop->getDomain()->toNative(),
                    'host' => $request->get('host'),
                    'locale' => $request->get('locale'),
                ])
            );

            return response()->json([
                'forceRedirectUrl' => $redirectUrl
            ], 403);
        }

        // Move on, everything's fine
        return $next($request);
    }
}
