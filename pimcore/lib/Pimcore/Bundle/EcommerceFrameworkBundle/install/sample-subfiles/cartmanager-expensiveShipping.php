<?php 

return [
    "tenant" => [
        "pricecalculator" => [
            "class" => "\\OnlineShop\\Framework\\CartManager\\CartPriceCalculator",
            "config" => [
                "modificators" => [
                    "shipping" => [
                        "class" => "\\OnlineShop\\Framework\\CartManager\\CartPriceModificator\\Shipping",
                        "config" => [
                            "charge" => "500.90"
                        ]
                    ]
                ]
            ]
        ]
    ]
];
