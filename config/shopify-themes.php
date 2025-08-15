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
            'sections/main-cart-items.liquid' => [
                [
                    // Targeting the final price in cart items
                    'target' => '<strong class="cart-item__final-price product-option">',
                    'inject'  => "{% render 'price-eur', price: item.final_price %}",
                ],
                [
                    'target' => '<div class="product-option">',
                    'inject'  => "{% render 'price-eur', price: item.original_price %}",
                ],
                // next 3 exist 2 times in main-cart-items.liquid(checked in Dawn)
                [
                    'target' => '<s class="cart-item__old-price price price--end">',
                    'inject'  => "{% render 'price-eur', price: item.original_line_price %}",
                ],
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
];
