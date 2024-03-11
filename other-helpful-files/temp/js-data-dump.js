const parent_data = {
    "id": "paypal_button_0",
    "nonce": "87956df68b",
    "env": "sandbox",
    "client_id": "Ad_zFEIjrMlQdw4ATpQr6MmpjQ5gjpy0zNweNQeKKNK5q3ZFBSSAqVppaXM7KdZ5quEGCYOZQYprbwXG",
    "price": "999.99",
    "quantity": 2,
    "tax": "",
    "shipping": "",
    "shipping_per_quantity": "",
    "shipping_enable": false,
    "dec_num": 2,
    "thousand_sep": ",",
    "dec_sep": ".",
    "curr_pos": "left",
    "tos_enabled": 0,
    "custom_quantity": false,
    "custom_amount": false,
    "currency": "USD",
    "currency_symbol": "$",
    "coupons_enabled": 0,
    "product_id": 301,
    "name": "WPEC Product 4",
    "stock_enabled": false,
    "stock_items": 0,
    "variations": {
        "groups": []
    },
    "btnStyle": {
        "height": 35,
        "shape": "rect",
        "label": "pay",
        "color": "silver",
        "layout": "vertical"
    },
    "orig_price": 999.99,
    "newPrice": 999.99,
    "total": 1999.98,
    "subtotal": 1999.98
}


const order_data = {
    "intent": "CAPTURE",
    "payment_source": {
        "paypal": {
            "experience_context": {
                "payment_method_preference": "IMMEDIATE_PAYMENT_REQUIRED",
                "shipping_preference": "NO_SHIPPING",
                "user_action": "PAY_NOW"
            }
        }
    },
    "purchase_units": [
        {
            "amount": {
                "value": 1999.98,
                "currency_code": "USD",
                "breakdown": {
                    "item_total": {
                        "currency_code": "USD",
                        "value": 1999.98
                    }
                }
            },
            "items": [
                {
                    "name": "WPEC Product 4",
                    "quantity": 2,
                    "unit_amount": {
                        "value": "999.99",
                        "currency_code": "USD"
                    }
                }
            ]
        }
    ]
}