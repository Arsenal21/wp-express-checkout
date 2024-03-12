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
                "shipping_preference": "GET_FROM_FILE",
                "user_action": "PAY_NOW"
            }
        }
    },
    "purchase_units": [
        {
            "amount": {
                "value": 337.75,
                "currency_code": "USD",
                "breakdown": {
                    "item_total": {
                        "currency_code": "USD",
                        "value": 300
                    },
                    "tax_total": {
                        "currency_code": "USD",
                        "value": 30
                    },
                    "shipping": {
                        "currency_code": "USD",
                        "value": 7.75
                    }
                }
            },
            "items": [
                {
                    "name": "WPEC Product 4",
                    "quantity": 3,
                    "unit_amount": {
                        "value": "100",
                        "currency_code": "USD"
                    },
                    "tax": {
                        "currency_code": "USD",
                        "value": 10
                    }
                }
            ]
        }
    ]
}

const order_data_2 = {
    "intent": "CAPTURE",
    "payment_source": {
        "paypal": {
            "experience_context": {
                "payment_method_preference": "IMMEDIATE_PAYMENT_REQUIRED",
                "shipping_preference": "GET_FROM_FILE",
                "user_action": "PAY_NOW"
            }
        }
    },
    "purchase_units": [
        {
            "amount": {
                "value": 269.5,
                "currency_code": "USD",
                "breakdown": {
                    "item_total": {
                        "currency_code": "USD",
                        "value": 240
                    },
                    "tax_total": {
                        "currency_code": "USD",
                        "value": 24
                    },
                    "shipping": {
                        "currency_code": "USD",
                        "value": 5.5
                    }
                }
            },
            "items": [
                {
                    "name": "WPEC Product 4",
                    "quantity": 2,
                    "unit_amount": {
                        "value": 120,
                        "currency_code": "USD"
                    },
                    "tax": {
                        "currency_code": "USD",
                        "value": 12
                    }
                }
            ]
        }
    ]
}

const wpec_data = {
    "id": "paypal_button_0",
    "nonce": "fb2167b6d5",
    "env": "sandbox",
    "client_id": "Ad_zFEIjrMlQdw4ATpQr6MmpjQ5gjpy0zNweNQeKKNK5q3ZFBSSAqVppaXM7KdZ5quEGCYOZQYprbwXG",
    "price": "100",
    "quantity": 3,
    "tax": "10",
    "shipping": "3.25",
    "shipping_per_quantity": "1.5",
    "shipping_enable": false,
    "dec_num": 2,
    "thousand_sep": ",",
    "dec_sep": ".",
    "curr_pos": "left",
    "tos_enabled": 0,
    "custom_quantity": true,
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
    "orig_price": 100,
    "newPrice": 100,
    "tax_amount": 10,
    "total": 337.75,
    "subtotal": 337.75
}

const wpec_data_2 = {
    "id": "paypal_button_0",
    "nonce": "fb2167b6d5",
    "env": "sandbox",
    "client_id": "Ad_zFEIjrMlQdw4ATpQr6MmpjQ5gjpy0zNweNQeKKNK5q3ZFBSSAqVppaXM7KdZ5quEGCYOZQYprbwXG",
    "price": 120,
    "quantity": 2,
    "tax": "10",
    "shipping": "3",
    "shipping_per_quantity": "1.25",
    "shipping_enable": false,
    "dec_num": 2,
    "thousand_sep": ",",
    "dec_sep": ".",
    "curr_pos": "left",
    "tos_enabled": 0,
    "custom_quantity": true,
    "custom_amount": false,
    "currency": "USD",
    "currency_symbol": "$",
    "coupons_enabled": 0,
    "product_id": 301,
    "name": "WPEC Product 4",
    "stock_enabled": false,
    "stock_items": 0,
    "variations": {
        "0": {
            "names": [
                "Red",
                "Green",
                "Blue"
            ],
            "prices": [
                "5",
                "10",
                "2"
            ],
            "urls": [
                "",
                "",
                ""
            ],
            "opts": "0"
        },
        "1": {
            "names": [
                "Small",
                "Medium",
                "Large"
            ],
            "prices": [
                "5",
                "0",
                "10"
            ],
            "urls": [
                "",
                "",
                ""
            ],
            "opts": "0"
        },
        "groups": [
            "Color",
            "Size"
        ],
        "applied": [
            "1",
            "2"
        ]
    },
    "btnStyle": {
        "height": 35,
        "shape": "rect",
        "label": "pay",
        "color": "silver",
        "layout": "vertical"
    },
    "orig_price": 100,
    "newPrice": 120,
    "tax_amount": 12,
    "total": 269.5,
    "subtotal": 269.5
}

const order_data_3 = {
    "intent": "CAPTURE",
    "payment_source": {
        "paypal": {
            "experience_context": {
                "payment_method_preference": "IMMEDIATE_PAYMENT_REQUIRED",
                "shipping_preference": "GET_FROM_FILE",
                "user_action": "PAY_NOW"
            }
        }
    },
    "purchase_units": [
        {
            "amount": {
                "value": 126.5,
                "currency_code": "USD",
                "breakdown": {
                    "item_total": {
                        "currency_code": "USD",
                        "value": 220
                    },
                    "tax_total": {
                        "currency_code": "USD",
                        "value": 11
                    },
                    "shipping": {
                        "currency_code": "USD",
                        "value": 5.5
                    },
                    "discount": {
                        "currency_code": "USD",
                        "value": 110
                    }
                }
            },
            "items": [
                {
                    "name": "WPEC Product 4",
                    "quantity": 2,
                    "unit_amount": {
                        "value": 110,
                        "currency_code": "USD"
                    },
                    "tax": {
                        "currency_code": "USD",
                        "value": 5.5
                    }
                }
            ]
        }
    ]
}

const wpec_data_3 = {
    "id": "paypal_button_3",
    "nonce": "0262920d38",
    "env": "sandbox",
    "client_id": "Ad_zFEIjrMlQdw4ATpQr6MmpjQ5gjpy0zNweNQeKKNK5q3ZFBSSAqVppaXM7KdZ5quEGCYOZQYprbwXG",
    "price": 110,
    "quantity": 2,
    "tax": "10",
    "shipping": "3",
    "shipping_per_quantity": "1.25",
    "shipping_enable": false,
    "dec_num": 2,
    "thousand_sep": ",",
    "dec_sep": ".",
    "curr_pos": "left",
    "tos_enabled": 0,
    "custom_quantity": true,
    "custom_amount": false,
    "currency": "USD",
    "currency_symbol": "$",
    "coupons_enabled": 1,
    "product_id": 301,
    "name": "WPEC Product 4",
    "stock_enabled": false,
    "stock_items": 0,
    "variations": {
        "0": {
            "names": [
                "Red",
                "Green",
                "Blue"
            ],
            "prices": [
                "5",
                "10.555",
                "2"
            ],
            "urls": [
                "",
                "",
                ""
            ],
            "opts": "0"
        },
        "1": {
            "names": [
                "Small",
                "Medium",
                "Large"
            ],
            "prices": [
                "5",
                "0",
                "10"
            ],
            "urls": [
                "",
                "",
                ""
            ],
            "opts": "0"
        },
        "groups": [
            "Color",
            "Size"
        ],
        "applied": [
            "0",
            "0"
        ]
    },
    "btnStyle": {
        "height": 35,
        "shape": "rect",
        "label": "pay",
        "color": "silver",
        "layout": "vertical"
    },
    "orig_price": 100,
    "newPrice": 55,
    "tax_amount": 5.5,
    "total": 126.5,
    "subtotal": 247.5,
    "discount": "50",
    "discountType": "perc",
    "couponCode": "C",
    "discountAmount": 110
}

const coupon_perc = (
    [code] => c
    [valid] => 1
    [id] => 315
    [discount] => 50
    [discountType] => perc
)

const coupon_fixed = (
    [code] => d
    [valid] => 1
    [id] => 316
    [discount] => 10
    [discountType] => fixed
)