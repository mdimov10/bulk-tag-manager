<?php

namespace App\Http\Controllers;

use App\Models\PrivacyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WebhookController extends Controller
{
      public function handleCustomerDataRequest(Request $request)
    {
        try {
            PrivacyRequest::create([
                'type' => 'customers/data_request',
                'shop_domain' => $request->get('shop_domain', 'unknown'),
                'payload' => json_encode($request->all()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save customers/data_request webhook: ' . $e->getMessage());
        }

        try {
            Mail::raw(
                "New customers/data_request webhook received:\n\n" . json_encode($request->all(), JSON_PRETTY_PRINT),
                function ($message) {
                    $message->to('momchil.dimov10@gmail.com')
                            ->subject('Shopify Webhook: customers/data_request');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send email for customers/data_request: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    public function handleCustomerRedact(Request $request)
    {
        try {
            PrivacyRequest::create([
                'type' => 'customers/redact',
                'shop_domain' => $request->get('shop_domain', 'unknown'),
                'payload' => json_encode($request->all()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save customers/redact webhook: ' . $e->getMessage());
        }

        try {
            Mail::raw(
                "New customers/redact webhook received:\n\n" . json_encode($request->all(), JSON_PRETTY_PRINT),
                function ($message) {
                    $message->to('momchil.dimov10@gmail.com')
                            ->subject('Shopify Webhook: customers/redact');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send email for customers/redact: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    public function handleShopRedact(Request $request)
    {
        try {
            PrivacyRequest::create([
                'type' => 'shop/redact',
                'shop_domain' => $request->get('shop_domain', 'unknown'),
                'payload' => json_encode($request->all()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save shop/redact webhook: ' . $e->getMessage());
        }

        try {
            Mail::raw(
                "New shop/redact webhook received:\n\n" . json_encode($request->all(), JSON_PRETTY_PRINT),
                function ($message) {
                    $message->to('momchil.dimov10@gmail.com')
                            ->subject('Shopify Webhook: shop/redact');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send email for shop/redact: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }
}
