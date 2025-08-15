<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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
