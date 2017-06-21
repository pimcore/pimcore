<?php

return [
    'ecommerceframework' => [
       /* general settings for ecommerce framework environment */
        'environment' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Environment',
            'config' => [
                'defaultCurrency' => 'EUR'
            ]
        ],
        /* general settings for cart manager */
        'cartmanager' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\MultiCartManager',
            'config' => [
                /* default cart implementation that is used */
                'cart' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\Cart',
                    'guest' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\SessionCart'
                    ]
                ],
                /* default price calculator for cart */
                'pricecalculator' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceCalculator',
                    'config' => [
                        /* price modificators for cart, e.g. for shipping-cost, special discounts, ... */
                        'modificators' => [
                            'shipping' => [
                                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceModificator\\Shipping',
                                'config' => [
                                    'charge' => '5.90'
                                ]
                            ]
                        ]
                    ]
                ],
                /*  special configuration for specific checkout tenants
                    - for not specified elements the default configuration is used as fallback
                    - active tenant is set at \Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment::setCurrentCheckoutTenant() */
                'tenants' => [
                    'noShipping' => [
                        'pricecalculator' => [
                            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceCalculator',
                            'config' => [
                                'modificators' => []
                            ]
                        ]
                    ]
                    /* you also can use external files for additional configuration */
                    /* "expensiveShipping" =>[ "file" => "\\eommerce\\cartmanager-expensiveShipping.php ] */
                ],

            ]
        ],
        'pricesystems' => [
            /* Define one or more price systems. The products getPriceSystemName method need to return a name here defined */
            'pricesystem' => [
                [
                    'name' => 'default',
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PriceSystem\\AttributePriceSystem',
                    'config' => [
                        'attributename' => 'price'
                    ]
                ],
                [
                    'name' => 'defaultOfferToolPriceSystem',
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PriceSystem\\AttributePriceSystem',
                    'config' => [
                        'attributename' => 'price'
                    ]
                ]
            ]
        ],
        'availablitysystems' => [
            /* Define one or more availability systems. The products getAvailabilitySystemName method need to return a name here defined */
            'availablitysystem' => [
                'name' => 'default',
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\AvailabilitySystem\\AttributeAvailabilitySystem'
            ]
        ],
        /* general settings for checkout manager */
        'checkoutmanager' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CheckoutManager\\CheckoutManager',
            'config' => [
                /* define different checkout steps which need to be committed before commit of order is possible */
                'steps' => [
                    'deliveryaddress' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CheckoutManager\\DeliveryAddress'
                    ],
                    /* example step from the Ecommerce demo, which extends AbstractStep */
                    /*"confirm" => [
                        "class" => "\\AppBundle\\Ecommerce\\Checkout\\Confirm"
                    ]*/
                ],
                /* optional
                     -> define payment provider which should be used for payment.
                     -> payment providers are defined in payment manager section. */
                'payment' => [
                    'provider' => 'qpay'
                ],
                /* define used commit order processor */
                'commitorderprocessor' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CheckoutManager\\CommitOrderProcessor'
                ],
                /* settings for confirmation mail sent to customer after order is finished.
                     also could be defined defined directly in commit order processor (e.g. when different languages are necessary)
                 */
                'mails' => [
                    'confirmation' => '/en/emails/order-confirmation'
                ],
                /* special configuration for specific checkout tenants */
                'tenants' => [
                    'paypal' => [
                        'payment' => [
                            'provider' => 'paypal'
                        ]
                    ],
                    'datatrans' => [
                        'payment' => [
                            'provider' => 'datatrans'
                        ]
                    ]
                ]
            ]
        ],
        'paymentmanager' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\PaymentManager',
            'statusClass' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Status',
            'config' => [
                'provider' => [
                    [
                        'name' => 'datatrans',
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\Datatrans',
                        'mode' => 'sandbox',
                        'config' => [
                            'sandbox' => [
                                'merchantId' => '1000011011',
                                'sign' => '30916165706580013',
                                'digitalSignature' => '0'
                            ],
                            'live' => [
                                'merchantId' => '',
                                'sign' => '',
                                'digitalSignature' => '0'
                            ]
                        ]
                    ],
                    [
                        /* https://integration.wirecard.at/doku.php/wcp:integration */
                        /* https://integration.wirecard.at/doku.php/demo:demo_data */
                        'name' => 'qpay',
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\QPay',
                        'mode' => 'sandbox',
                        'config' => [
                            'sandbox' => [
                                'secret' => 'B8AKTPWBRMNBV455FG6M2DANE99WU2',
                                'customer' => 'D200001',
                                'toolkitPassword' => 'jcv45z',
                                /* define optional properties which can be used in initPayment (see Wirecard documentation)
                                https://git.io/v2ty1 */
                                /* "optionalPaymentProerties" => [
                                    "property" => [
                                        "paymentType",
                                        "financialInstitution"
                                    ]
                                ], */
                                /*  set hash algorithm to HMAC-SHA512
                                https://git.io/v2tyV */
                                /* ["hashAlgorithm" => "hmac_sha512"] */
                            ],
                            'test' => [
                                'secret' => 'CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ',
                                'customer' => 'D200411',
                                'toolkitPassword' => '2g4fq2m'
                            ],
                            'live' => [
                                'secret' => '',
                                'customer' => '',
                                'toolkitPassword' => ''
                            ]
                        ]
                    ],
                    [
                        'name' => 'paypal',
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\PayPal',
                        'mode' => 'sandbox',
                        'config' => [
                            'sandbox' => [
                                'api_username' => 'paypal-facilitator_api1.i-2xdream.de',
                                'api_password' => '1375366858',
                                'api_signature' => 'AT2PJj7VTo5Rt.wM6enrwOFBoD1fACBe1RbAEMsSshWFRhpvjAuPR8wD'
                            ],
                            'live' => [
                                'api_username' => '',
                                'api_password' => '',
                                'api_signature' => ''
                            ]
                        ]
                    ],
                    [
                        'name' => 'seamless',
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\WirecardSeamless',
                        'mode' => 'sandbox',
                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method-selection.html.php',
                        'js' => '/website/static/js/payment/wirecard-seamless/frontend.js',
                        'config' => [
                            'sandbox' => [
                                'customerId' => 'D200001',
                                'customerStatement' => 'Pimcore Shop',
                                'shopId' => 'qmore',
                                'secret' => 'B8AKTPWBRMNBV455FG6M2DANE99WU2',
                                'password' => 'jcv45z',
                                //define hash algorithm - possbile values are sha512 and hmac_sha512
                                'hashAlgorithm' => 'hmac_sha512',
                                //activates delivery of all cart items to wirecard for paypal integration
                                'paypalActivateItemLevel' => true,
                                'iframeCssUrl' => '/website/static/css/payment-iframe.css',
                                'paymentMethods' => [
                                    'PREPAYMENT' => [
                                        'icon' => '/website/static/img/wirecard-seamless/prepayment.png',
                                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/prepayment.html.php'
                                    ],
                                    'CCARD' => [
                                        'icon' => '/website/static/img/wirecard-seamless/ccard.png',
                                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/ccard.html.php'
                                    ],
                                    'PAYPAL' => [
                                        'icon' => '/website/static/img/wirecard-seamless/paypal.png'
                                    ],
                                    'SOFORTUEBERWEISUNG' => [
                                        'icon' => '/website/static/img/wirecard-seamless/sue.png'
                                    ],
                                    'INVOICE' => [
                                        'icon' => '/website/static/img/wirecard-seamless/payolution.png',
                                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/invoice.html.php'
                                    ]
                                ]
                            ],
                            'live' => [
                                'customerId' => '',
                                'customerStatement' => 'Pimcore Shop',
                                'shopId' => '',
                                'secret' => '',
                                'password' => '',
                                //define hash algorithm - possbile values are sha512 and hmac_sha512
                                'hashAlgorithm' => 'hmac_sha512',
                                //activates delivery of all cart items to wirecard for paypal integration
                                'paypalActivateItemLevel' => true,
                                'iframeCssUrl' => '/website/static/css/payment-iframe.css?elementsclientauth=disabled',
                                'paymentMethods' => [
                                    'PREPAYMENT' => [
                                        'icon' => '/website/static/img/wirecard-seamless/prepayment.png',
                                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/prepayment.html.php'
                                    ],
                                    'CCARD' => [
                                        'icon' => '/website/static/img/wirecard-seamless/ccard.png',
                                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/ccard.html.php'
                                    ],
                                    'PAYPAL' => [
                                        'icon' => '/website/static/img/wirecard-seamless/paypal.png'
                                    ],
                                    'SOFORTUEBERWEISUNG' => [
                                        'icon' => '/website/static/img/wirecard-seamless/sue.png'
                                    ],
                                    'INVOICE' => [
                                        'icon' => '/website/static/img/wirecard-seamless/payolution.png',
                                        'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/invoice.html.php'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'productindex' => [
            /* to disable default tenant, add parameter  "disableDefaultTenant"=>true  to productindex element  */

            /* add columns for general fulltext search index of productlist - they must be part of the column configuration below  */
            'generalSearchColumns' => [
                /* column definition for product index */
                [
                    'name' => 'name'
                ],
                [
                    'name' => 'seoname'
                ]
            ],
            /* column definition for product index */
            'columns' => [
                /* included config files will be merged with given columns
                 *
                 * placeholder values in this file ("locale" => "%locale%") will be replaced by
                 * the given placeholder value (eg. "de_AT")
                 */

                /*[
                    "file" => "/ecommerce/additional-index.php",
                    "placeholders" => ["%locale%" => "de_AT"]
                ],*/
                [
                    'name' => 'name',
                    'type' => 'varchar(255)',
                    'locale' => 'en_GB',
                    'filtergroup' => 'string'
                ],
                [
                    'name' => 'seoname',
                    'type' => 'varchar(255)',
                    'filtergroup' => 'string'
                ],

                /* SAMPLE FOR FURTHER ATTRIBUTES */

                /*  [
                    "name" => "color",
                    "type" => "varchar(255)",
                    "filtergroup" => "multiselect"
                ],
                [
                    "name" => "gender",
                    "type" => "varchar(100)",
                    "filtergroup" => "multiselect"
                ],
                [
                    "name" => "features",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\DefaultObjects",
                    "filtergroup" => "relation"
                ],
                [
                    "name" => "technologies",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\DefaultObjects",
                    "filtergroup" => "relation"
                ],
                [
                    "name" => "tentTentPegs",
                    "type" => "varchar(50)",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "tentSpecifications",
                        "fieldname" => "tentPegs"
                    ]
                ],
                [
                    "name" => "tentWaterproofRain",
                    "type" => "varchar(50)",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "tentSpecifications",
                        "fieldname" => "waterproofRain"
                    ]
                ],
                [
                    "name" => "tentWaterproofGround",
                    "type" => "varchar(50)",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "tentSpecifications",
                        "fieldname" => "waterproofGround"
                    ]
                ],
                [
                    "name" => "rucksacksVolume",
                    "type" => "double",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\Numeric",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "rucksackSpecs",
                        "fieldname" => "volume"
                    ]
                ],
                [
                    "name" => "rucksacksWeight",
                    "type" => "double",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\Numeric",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "rucksackSpecs",
                        "fieldname" => "weight"
                    ]
                ],
                [
                    "name" => "rucksacksLoad",
                    "type" => "double",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\Numeric",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "rucksackSpecs",
                        "fieldname" => "load"
                    ]
                ],
                [
                    "name" => "matsWidth",
                    "type" => "double",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "matsSpecs",
                        "fieldname" => "width"
                    ]
                ],
                [
                    "name" => "matsLength",
                    "type" => "double",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "matsSpecs",
                        "fieldname" => "length"
                    ]
                ],
                [
                    "name" => "matsHeight",
                    "type" => "double",
                    "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                    "filtergroup" => "string",
                    "config" => [
                        "brickfield" => "specificAttributes",
                        "bricktype" => "matsSpecs",
                        "fieldname" => "height"
                    ]
                ],
                [
                    "name" => "simularity_color",
                    "fieldname" => "color",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\Soundex"
                ],
                [
                    "name" => "simularity_gender",
                    "fieldname" => "gender",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\Soundex"
                ],
                [
                    "name" => "simularity_category",
                    "fieldname" => "categories",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\ObjectIdSum"
                ],
                [
                    "name" => "simularity_feature",
                    "fieldname" => "features",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\ObjectIdSum"
                ],
                [
                    "name" => "simularity_technolgy",
                    "fieldname" => "technologies",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\ObjectIdSum"
                ],
                [
                    "name" => "simularity_attributes",
                    "fieldname" => "attributes",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\ObjectIdSum"
                ] */
            ],
            /* configuration of different tenants */
            'tenants' => ''

            /*  "elasticsearch" => [
                    "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Config\\ElasticSearch",
                    "file" => "/website/var/plugins/EcommerceFramework/assortment-tenant-elasticsearch.php"
                ],
                "sampletenant" => [
                    "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Config\\DefaultMysqlSubTenantConfig",
                    "generalSearchColumns" => [
                        [
                            "name" => "name"
                        ],
                        [
                            "name" => "shortDescription"
                        ]
                    ],
                    "columns" => [
                        [
                            "name" => "name",
                            "type" => "varchar(255)"
                        ],
                        [
                            "name" => "shortDescription",
                            "type" => "varchar(255)"
                        ],
                        [
                            "name" => "supplierRemoteId",
                            "type" => "varchar(255)"
                        ],
                        [
                            "name" => "mainImage",
                            "type" => "int",
                            "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\AssetId"
                        ],
                        [
                            "name" => "hardDiskDriveCapacity",
                            "type" => "DOUBLE",
                            "getter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Getter\\DefaultBrickGetter",
                            "interpreter" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\IndexService\\Interpreter\\DimensionUnitField",
                            "config" => [
                                "brickfield" => "specialFeatures",
                                "bricktype" => "Memory",
                                "fieldname" => "hardDiskDriveCapacity"
                            ]
                        ]
                    ] */
        ],
        /*  assign backend implementations and views to filter type field collections

            helper = tool for pimcore backend controller to get possible group by values for a certain field
                     (used by object data type IndexFieldSelection, e.g. in filter definitions)
         */
        'filtertypes' => [
            'helper' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterGroupHelper',
            'FilterNumberRange' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\NumberRange',
                'script' => ':Shop/filters:range.html.php'
            ],
            'FilterNumberRangeSelection' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\NumberRangeSelection',
                'script' => ':Shop/filters:numberrange.html.php'
            ],
            'FilterSelect' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\Select',
                'script' => ':Shop/filters:select.html.php'
            ],
            'FilterSelectFromMultiSelect' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\SelectFromMultiSelect',
                'script' => ':Shop/filters:select.html.php'
            ],
            'FilterMultiSelect' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\MultiSelect',
                'script' => ':Shop/filters:multiselect.html.php'
            ],
            'FilterMultiSelectFromMultiSelect' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\MultiSelectFromMultiSelect',
                'script' => ':Shop/filters:multiselect.html.php'
            ],
            'FilterMultiRelation' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\MultiSelectRelation',
                'script' => ':Shop/filters:multiselect-relation.html.php'
            ],
            'FilterCategory' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\SelectCategory',
                'script' => ':Shop/filters:select_category.html.php'
            ],
            'FilterRelation' => [
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\FilterService\\FilterType\\SelectRelation',
                'script' => ':Shop/filters:object_relation.html.php'
            ]
        ],

        /* pricing manager */
        'pricingmanager' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\PricingManager',
            'config' => [
                /* "disabled" => true, */

                /* rule and priceinfo */
                'rule' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Rule'
                ],
                'priceInfo' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\PriceInfo'
                ],
                /* rule conditions */
                'condition' => [
                    'Bracket' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\Bracket'
                    ],
                    'DateRange' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\DateRange'
                    ],
                    'CartAmount' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\CartAmount'
                    ],
                    'CatalogProduct' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\CatalogProduct'
                    ],
                    'CatalogCategory' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\CatalogCategory'
                    ],
                    'Token' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\Token'
                    ],
                    'VoucherToken' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\VoucherToken'
                    ],
                    'Sold' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\Sold'
                    ],
                    'Tenant' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\Tenant'
                    ],
                    'Sales' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\Sales'
                    ],
                    'ClientIp' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Condition\\ClientIp'
                    ]
                ],
                /* rule actions */
                'action' => [
                    'ProductDiscount' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Action\\ProductDiscount'
                    ],
                    'CartDiscount' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Action\\CartDiscount'
                    ],
                    'Gift' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Action\\Gift'
                    ],
                    'FreeShipping' => [
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PricingManager\\Action\\FreeShipping'
                    ]
                ],
                //Checkout Tenants for Pricing Rules - e.g. to disable pricing rules for one tenant completely
                'tenants' => [
                    'noPricingRules' => [
                        'disabled' => 'false'
                    ]
                ]
            ]
        ],
        /* Offertool */
        'offertool' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OfferTool\\DefaultService',
            'orderstorage' => [
                'offerClass' => '\\Pimcore\\Model\\Object\\OfferToolOffer',
                'offerItemClass' => '\\Pimcore\\Model\\Object\\OfferToolOfferItem',
                'parentFolderPath' => '/offertool/offers/%Y/%m'
            ]
        ],
        /* order manager */
        'ordermanager' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\OrderManager',
            'config' => [
                'orderList' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\Order\\Listing',
                    'classItem' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\Order\\Listing\\Item'
                ],
                'orderAgent' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\OrderManager\\Order\\Agent'
                ],
                /* settings for order storage - pimcore class names for oder and order items */
                'orderstorage' => [
                    'orderClass' => '\\Pimcore\\Model\\Object\\OnlineShopOrder',
                    'orderItemClass' => '\\Pimcore\\Model\\Object\\OnlineShopOrderItem'
                ],
                /* parent folder for order objects - either ID or path can be specified. path is parsed by strftime. */
                'parentorderfolder' => '/order/%Y/%m/%d',
                /* special configuration for specific checkout tenants */
                'tenants' => [
                    'otherFolder' => [
                        'parentorderfolder' => '/order_otherfolder/%Y/%m/%d'
                    ]
                ]
            ]
        ],
        /* voucher service - define voucher service implementation class and map token managers */
        'voucherservice' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\VoucherService\\DefaultService',
            /* assign backend implementations to voucher token type field collections */
            'tokenmanagers' => [
                'VoucherTokenTypePattern' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\VoucherService\\TokenManager\\Pattern'
                ],
                'VoucherTokenTypeSingle' => [
                    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\VoucherService\\TokenManager\\Single'
                ]
            ],
            'config' => [
                /*  Reservations older than x MINUTES get removed by maintenance task */
                'reservations' => [
                    'duration' => '5'
                ],
                /* Statistics older than x DAYS get removed by maintenance task */
                'statistics' => [
                    'duration' => '30'
                ]
            ]
        ],

        /*  tracking manager - define which trackers (e.g. Google Analytics Universal Ecommerce) are active and should
     be called when you track something via TrackingManager */
        'trackingmanager' => [
            'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\TrackingManager',
            'config' => [
                'trackers' => [
                    [
                        'name' => 'GoogleAnalyticsEnhancedEcommerce',
                        'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\Tracker\\Analytics\\EnhancedEcommerce',
                        'trackingItemBuilder' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\Tracking\\TrackingItemBuilder'
                    ]
                ]
            ]
        ],

        /* pimcore Ecommerce Framework Menu */
        'pimcore' => [
            'menu' => [
                'pricingRules' => [
                    'disabled' => '0'
                ],
                'orderlist' => [
                    'disabled' => '0',
                    'route' => '/admin/ecommerceframework/admin-order/list'
                ]
            ]
        ]
    ]
];
