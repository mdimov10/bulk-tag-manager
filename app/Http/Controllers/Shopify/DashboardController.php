<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getShopSettings(Request $request)
    {
        $shop = auth()->user();

        return response()->json([
            'freemium' => $shop->shopify_freemium ?? false,
            'plan_id' => $shop->plan_id,
            'shop_domain' => $shop->getDomain()->toNative(),
        ]);
    }

    public function activateCustomPlan(Request $request)
    {
        $shop = auth()->user();
        $promoCode = $request->promo_code;
        $noPromo = $request->boolean('no_promo');

        $defaultPlanId = 1;  // normal visible plan ID
        $hiddenPlanId = 2;   // discounted hidden plan ID

        try {
            if ($noPromo) {
                $shop->draft_plan_id = $defaultPlanId;
                $shop->shopify_freemium = false;
                $shop->save();

                return response()->json(['success' => true]);
            }

            $validCodes = [
                'LUKA25' => $hiddenPlanId,
            ];

            if (!isset($validCodes[$promoCode])) {
                return response()->json(['error' => 'Invalid promo code'], 422);
            }

            $shop->draft_plan_id = $validCodes[$promoCode];
            $shop->shopify_freemium = false;
            $shop->save();
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }

        return response()->json(['success' => true]);
    }

    public function getLocales()
    {
        $shop = auth()->user();

        $query = <<<GRAPHQL
        {
            shopLocales {
                locale
                name
            }
        }
        GRAPHQL;

        $response = $shop->api()->graph($query);
        $locales = $response['body']['data']['shopLocales'] ?? [];

        $installedLocales = array_flip($shop->dualPricingLocales->pluck('locale')->toArray());

        $result = collect($locales)->map(function ($locale) use ($installedLocales) {
            return [
                'locale' => $locale['locale'],
                'name' => $locale['name'],
                'installed' => isset($installedLocales[$locale['locale']]),
            ];
        });

        return response()->json($result);
    }
}
