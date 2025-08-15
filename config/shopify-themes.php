<?php

return [
    'shrine' => [
        'files' => [
            'snippets/price.liquid' => [
                // Case: regular price with main_price styling
                [
                    'target' => '<span class="price-item price-item--regular{% if main_price %} main-price accent-color-{{ block.settings.price_color }}{% endif %}">',
                    'inject' => "{% render 'price-eur', price: price %}",
                ],
                // Case: sale price with main_price styling
                [
                    'target' => '<span class="price-item price-item--sale price-item--last{% if main_price %} main-price accent-color-{{ block.settings.price_color }}{% endif %}">',
                    'inject' => "{% render 'price-eur', price: price %}",
                ],
            ],
            'snippets/cart-drawer.liquid' => [
                [
                    'target' => '<span class="price price--end cart-drawer__final-item-price accent-color-{{ settings.cart_price_color }}">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
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
    'shrine-pro' => [
        'files' => [
            'snippets/price.liquid' => [
                // Case: regular price with main_price styling
                [
                    'target' => '<span class="price-item price-item--regular{% if main_price %} main-price accent-color-{{ block.settings.price_color }}{% endif %}">',
                    'inject' => "{% render 'price-eur', price: price %}",
                ],
                // Case: sale price with main_price styling
                [
                    'target' => '<span class="price-item price-item--sale price-item--last{% if main_price %} main-price accent-color-{{ block.settings.price_color }}{% endif %}">',
                    'inject' => "{% render 'price-eur', price: price %}",
                ],
            ],
            'snippets/cart-drawer.liquid' => [
                [
                    'target' => '<span class="price--end regular-price accent-color-{{ block.settings.price_color }}">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
                ],
                [
                    'target' => "{% capture subtotal_money %}<span class='cart-drawer__totals__row__money'>",
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
                    'target' => '<p class="totals__total-value">',
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
    'horizon' => [
        'files' => [
            'snippets/price.liquid' => [
                [
                    // Targeting the entire span with the class and price content
                    'target' => '<span class="price">',
                    'inject'  => "{% render 'price-eur', price: selected_variant.price %}",
                ],
            ],
            'snippets/cart-summary.liquid' => [
                [
                    'target' => '<text-component
        ref="cartTotal"
        value="{{ total_price | strip_html }}"
        class="cart__total-value cart-secondary-typography"
        {% comment %} Used by payment_terms web component {% endcomment %}
        data-cart-subtotal
      >',
                    'inject'  => "{% render 'price-eur', price: cart.total_price %}",
                ],
            ],
            'snippets/cart-products.liquid' => [
                [
                    'target' => '<text-component value="{{ price | strip_html }}">',
                    'inject'  => "{% render 'price-eur', price: item.final_line_price %}",
                ],
            ],
        ],
    ],
    'fintace-installer' => [
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
];
