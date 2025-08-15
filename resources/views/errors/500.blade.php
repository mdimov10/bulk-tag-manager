<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Euro Dual Pricing App</title>
</head>
<body>
    <h2>Euro Dual Pricing App</h2>
    @if (!request()->has('shop'))
        <p>
            It looks like you accessed this page without authenticating through a Shopify store.
            <br>
            This app is intended to be used inside the Shopify admin.
        </p>
    @else
        <p>
            An error occurred.
        </p>
    @endif
</body>
</html>
