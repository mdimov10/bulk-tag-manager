<?php

return [
    'dawn' => [
        'files' => [
            'snippets/price.liquid' => [
                [
                    // Targeting the entire span with the class and price content
                    'target' => '<span class="price-item price-item--regular">',
                    'inject'  => "{% render 'price-eur', price: price %}",
                ],
                [
                    'target' => '<span class="price-item price-item--sale price-item--last">',
                    'inject'  => "{% render 'price-eur', price: price %}",
                ],
            ],
            'snippets/cart-drawer.liquid' => [
                [
                    'target' => '<p class="totals__total-value">',
                    'inject'  => "{% render 'price-eur', price: cart.total_price %}",
                ],
            ],
            'sections/main-cart-items.liquid' => [
                [
                    'target' => '<dd class="price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
                ],
                [
                    'target' => '<span class="price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.original_line_price %}",
                ],
            ],
             'sections/main-cart-footer.liquid' => [
                [
                    // Targeting the final price in cart items
                    'target' => ' <p class="totals__total-value">',
                    'inject'  => "{% render 'price-eur', price: cart.total_price %}",
                ],
            ],
        ],
    ],
    'test-data' => [
        'files' => [
            'snippets/price.liquid' => [
                [
                    // Targeting the entire span with the class and price content
                    'target' => '<span class="price-item price-item--regular">',
                    'inject'  => "{% render 'price-eur', price: price %}",
                ],
                [
                    'target' => '<span class="price-item price-item--sale price-item--last">',
                    'inject'  => "{% render 'price-eur', price: price %}",
                ],
            ],
            'snippets/cart-drawer.liquid' => [
                [
                    'target' => '<span class="price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
                    'only_if_contains' => 'item.final_line_price',
                ],
                [
                    'target' => '<span class="price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.original_line_price %}",
                    'only_if_contains' => 'item.original_line_price',
                ],
                [
                    'target' => '<p class="totals__subtotal-value">',
                    'inject'  => "{% render 'price-eur', price: cart.total_price %}",
                ],
            ],
            'sections/main-cart-items.liquid' => [
                [
                    'target' => '<dd class="price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
                ],
                [
                    'target' => '<span class="price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.original_line_price %}",
                ],
            ],
             'sections/main-cart-footer.liquid' => [
                [
                    // Targeting the final price in cart items
                    'target' => '<p class="totals__subtotal-value">',
                    'inject'  => "{% render 'price-eur', price: cart.total_price %}",
                ],
            ],
        ],
    ],
    'sleek' => [
        'files' => [
            'snippets/price.liquid' => [
                [
                    // Targeting the entire span with the class and price content
                    'target' => '<span class="f-price-item f-price-item--regular">',
                    'inject'  => "{% render 'price-eur', price: price %}",
                ],
                [
                    'target' => '<span class="f-price-item f-price-item--sale">',
                    'inject'  => "{% render 'price-eur', price: price %}",
                ],
            ],
            'sections/main-cart-items.liquid' => [
                [
                    'target' => '<div class="price font-body-bolder">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
                ],
            ],
             'sections/main-cart-footer.liquid' => [
                [
                    // Targeting the final price in cart items
                    'target' => ' <span class="totals__subtotal-value h4 font-body-bolder">',
                    'inject'  => "{% render 'price-eur', price: cart.total_price %}",
                ],
            ],
        ],
    ],
];
