<?php

namespace App\Http\Controllers\Admin\Plans;

use App\Http\Controllers\Controller;
use Osiset\ShopifyApp\Util;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;

class PremiumController extends Controller
{
    public function __construct(private readonly ResponseFactory $responseFactory) {
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
       return $this->responseFactory->json(
           [
               'hasPremium' => $request->user()->plan?->price > 0
           ]
       );
    }

    public function store(Request $request) {
        $shop = $request->user();

        if ($shop->plan?->price > 0) {
            return $this->responseFactory->json();
        }

        $redirectUrl = route(
            Util::getShopifyConfig('route_names.billing'),
            array_merge($request->input(), [
                'shop' => $shop->getDomain()->toNative(),
                'host' => $request->get('host'),
                'locale' => $request->get('locale'),
            ])
        );

        return $this->responseFactory->noContent();
    }
}
