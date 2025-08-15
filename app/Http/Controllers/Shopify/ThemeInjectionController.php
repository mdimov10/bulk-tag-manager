<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Services\ThemeInstaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ThemeInjectionController extends Controller
{
    public function installSnippet(Request $request)
    {
        $request->validate([
            'locale' => 'required|string',
        ]);

        $shop = auth()->user();
        $locale = $request->input('locale');

        try {
            $shop->dualPricingLocales()->updateOrCreate(['locale' => $locale], []);
        } catch (\Exception $exception) {
            Log::info('Could not install snippet: ' . $exception->getMessage());
        }

        $installer = new ThemeInstaller($shop);
        $result = $installer->install();

        if ($result['success']) {
            $shop->expires_at = now()->addDays(30);
            $shop->save();
        } else {
            $shop->dualPricingLocales()->where('locale', $locale)->delete();
        }

        return response()->json($result);
    }

    public function removeSnippet(Request $request)
    {
        $shop = auth()->user();

        // If locale is passed: remove that one
        if ($request->filled('locale')) {
            $locale = $request->input('locale');

            $shop->dualPricingLocales()->where('locale', $locale)->delete();
        } else {
            // If no locale is passed: remove all
            $shop->dualPricingLocales()->delete();
        }

        $installer = new ThemeInstaller($shop);
        $success = $installer->remove();

        if ($success) {
            return response()->json(['message' => 'Snippet removed successfully.']);
        }

        return response()->json(['error' => 'Snippet removal failed.'], 500);
    }
}
