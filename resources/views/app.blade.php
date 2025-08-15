<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Document</title>

    <meta name="shopify-api-key" content="{{ env('SHOPIFY_API_KEY') }}" />
    @php
        $shopDomain = auth()->user()?->name;
        $host = request('host') ?? session('shopify_host');

        if (!$host && $shopDomain) {
            $storeName = str_replace('.myshopify.com', '', $shopDomain);
            $host = base64_encode("admin.shopify.com/store/{$storeName}");
        }
    @endphp

    @if ($host)
        <meta name="shopify-host" content="{{ $host }}">
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>

    @vitereactrefresh
    @vite('resources/js/index.jsx')
</head>
<body>
    <div id="app"></div>
</body>
</html>
