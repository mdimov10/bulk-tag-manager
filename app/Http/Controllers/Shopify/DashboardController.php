<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Models\StoreData;
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

        // Match with user's already saved locales
        $installedLocales = $shop->dualPricingLocales->pluck('installed', 'locale')->toArray();

        $result = collect($locales)->map(function ($locale) use ($installedLocales) {
            return [
                'locale' => $locale['locale'],
                'name' => $locale['name'],
                'installed' => $installedLocales[$locale['locale']] ?? false,
            ];
        });

        return response()->json($result);
    }
}
