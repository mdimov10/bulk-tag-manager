<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQLService
{
    protected $shop;

    public function __construct(User $shop)
    {
        $this->shop = $shop;
    }
}
