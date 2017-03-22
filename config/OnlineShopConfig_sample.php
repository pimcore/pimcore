<?php 

return [
    "onlineshop" => [
       /* general settings for onlineshop environment */
        "environment" => [
            "class" => "\\OnlineShop\\Framework\\Environment",
            "config" => [
                "defaultCurrency" => "EUR"
            ]
        ],
        /* general settings for cart manager */
        "cartmanager" => [
            "class" => "\\OnlineShop\\Framework\\CartManager\\MultiCartManager",
            "config" => [
                /* default cart implementation that is used */
                "cart" => [
                    "class" => "\\OnlineShop\\Framework\\CartManager\\Cart",
                    "guest" => [
                        "class" => "\\OnlineShop\\Framework\\CartManager\\SessionCart"
                    ]
                ],
                /* default price calculator for cart */
                "pricecalculator" => [
                    "class" => "\\OnlineShop\\Framework\\CartManager\\CartPriceCalculator",
                    "config" => [
                        /* price modificators for cart, e.g. for shipping-cost, special discounts, ... */
                        "modificators" => [
                            "shipping" => [
                                "class" => "\\OnlineShop\\Framework\\CartManager\\CartPriceModificator\\Shipping",
                                "config" => [
                                    "charge" => "5.90"
                                ]
                            ]
                        ]
                    ]
                ],
                /*  special configuration for specific checkout tenants
                    - for not specified elements the default configuration is used as fallback
                    - active tenant is set at \OnlineShop\Framework\IEnvironment::setCurrentCheckoutTenant() */
                "tenants" => [
                    "noShipping" => [
                        "pricecalculator" => [
                            "class" => "\\OnlineShop\\Framework\\CartManager\\CartPriceCalculator",
                            "config" => [
                                "modificators" => []
                            ]
                        ]
                    ]
                    /* you also can use external files for additional configuration */
                    /* "expensiveShipping" =>[ "file" => "\\website\\var\\plugins\\OnlineShopConfig\\cartmanager-expensiveShipping.php ] */ 
                ],
                
            ]
        ],
        "pricesystems" => [
            /* Define one or more price systems. The products getPriceSystemName method need to return a name here defined */
            "pricesystem" => [
                [
                    "name" => "default",
                    "class" => "\\OnlineShop\\Framework\\PriceSystem\\AttributePriceSystem",
                    "config" => [
                        "attributename" => "price"
                    ]
                ],
                [
                    "name" => "defaultOfferToolPriceSystem",
                    "class" => "\\OnlineShop\\Framework\\PriceSystem\\AttributePriceSystem",
                    "config" => [
                        "attributename" => "price"
                    ]
                ]
            ]
        ],
        "availablitysystems" => [
            /* Define one or more availability systems. The products getAvailabilitySystemName method need to return a name here defined */
            "availablitysystem" => [
                "name" => "default",
                "class" => "\\OnlineShop\\Framework\\AvailabilitySystem\\AttributeAvailabilitySystem"
            ]
        ],
        /* general settings for checkout manager */
        "checkoutmanager" => [
            "class" => "\\OnlineShop\\Framework\\CheckoutManager\\CheckoutManager",
            "config" => [
                /* define different checkout steps which need to be committed before commit of order is possible */
                "steps" => [
                    "deliveryaddress" => [
                        "class" => "\\OnlineShop\\Framework\\CheckoutManager\\DeliveryAddress"
                    ],
                    /* example step from the Ecommerce demo, which extends AbstractStep */
                    /*"confirm" => [
                        "class" => "\\Website\\OnlineShop\\Checkout\\Confirm"
                    ]*/
                ],
                /* optional
                     -> define payment provider which should be used for payment.
                     -> payment providers are defined in payment manager section. */
                "payment" => [
                    "provider" => "qpay"
                ],
                /* define used commit order processor */
                "commitorderprocessor" => [
                    "class" => "Website_OnlineShop_Order_Processor"
                ],
                /* settings for confirmation mail sent to customer after order is finished.
                     also could be defined defined directly in commit order processor (e.g. when different languages are necessary)
                 */
                "mails" => [
                    "confirmation" => "/en/emails/order-confirmation"
                ],
                /* special configuration for specific checkout tenants */
                "tenants" => [
                    "paypal" => [
                        "payment" => [
                            "provider" => "paypal"
                        ]
                    ],
                    "datatrans" => [
                        "payment" => [
                            "provider" => "datatrans"
                        ]
                    ]
                ]
            ]
        ],
        "paymentmanager" => [
            "class" => "\\OnlineShop\\Framework\\PaymentManager\\PaymentManager",
            "statusClass" => "\\OnlineShop\\Framework\\PaymentManager\\Status",
            "config" => [
                "provider" => [
                    [
                        "name" => "datatrans",
                        "class" => "\\OnlineShop\\Framework\\PaymentManager\\Payment\\Datatrans",
                        "mode" => "sandbox",
                        "config" => [
                            "sandbox" => [
                                "merchantId" => "1000011011",
                                "sign" => "30916165706580013",
                                "digitalSignature" => "0"
                            ],
                            "live" => [
                                "merchantId" => "",
                                "sign" => "",
                                "digitalSignature" => "0"
                            ]
                        ]
                    ],
                    [
                        /* https://integration.wirecard.at/doku.php/wcp:integration */
                        /* https://integration.wirecard.at/doku.php/demo:demo_data */
                        "name" => "qpay",
                        "class" => "\\OnlineShop\\Framework\\PaymentManager\\Payment\\QPay",
                        "mode" => "sandbox",
                        "config" => [
                            "sandbox" => [
                                "secret" => "B8AKTPWBRMNBV455FG6M2DANE99WU2",
                                "customer" => "D200001",
                                "toolkitPassword" => "jcv45z",
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
                            "test" => [
                                "secret" => "CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ",
                                "customer" => "D200411",
                                "toolkitPassword" => "2g4fq2m"
                            ],
                            "live" => [
                                "secret" => "",
                                "customer" => "",
                                "toolkitPassword" => ""
                            ]
                        ]
                    ],
                    [
                        "name" => "paypal",
                        "class" => "\\OnlineShop\\Framework\\PaymentManager\\Payment\\PayPal",
                        "mode" => "sandbox",
                        "config" => [
                            "sandbox" => [
                                "api_username" => "paypal-facilitator_api1.i-2xdream.de",
                                "api_password" => "1375366858",
                                "api_signature" => "AT2PJj7VTo5Rt.wM6enrwOFBoD1fACBe1RbAEMsSshWFRhpvjAuPR8wD"
                            ],
                            "live" => [
                                "api_username" => "",
                                "api_password" => "",
                                "api_signature" => ""
                            ]
                        ]
                    ]
                ]
            ]
        ],
        "productindex" => [
            /* to disable default tenant, add parameter  "disableDefaultTenant"=>true  to productindex element  */
            
            /* add columns for general fulltext search index of productlist - they must be part of the column configuration below  */
            "generalSearchColumns" => [
                /* column definition for product index */
                [
                    "name" => "name"
                ],
                [
                    "name" => "seoname"
                ]
            ],
            /* column definition for product index */
            "columns" => [
                /* included config files will be merged with given columns
                 *
                 * placeholder values in this file ("locale" => "%locale%") will be replaced by
                 * the given placeholder value (eg. "de_AT")
                 */

                /*[
                    "file" => "/website/var/plugins/EcommerceFramework/additional-index.php",
                    "placeholders" => ["%locale%" => "de_AT"]
                ],*/
                [
                    "name" => "name",
                    "type" => "varchar(255)",
                    "locale" => "en_GB",
                    "filtergroup" => "string"
                ],
                [
                    "name" => "seoname",
                    "type" => "varchar(255)",
                    "filtergroup" => "string"
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
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\DefaultObjects",
                    "filtergroup" => "relation"
                ],
                [
                    "name" => "technologies",
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\DefaultObjects",
                    "filtergroup" => "relation"
                ],
                [
                    "name" => "tentTentPegs",
                    "type" => "varchar(50)",
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\Numeric",
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\Numeric",
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\Numeric",
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
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
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\Soundex"
                ],
                [
                    "name" => "simularity_gender",
                    "fieldname" => "gender",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\Soundex"
                ],
                [
                    "name" => "simularity_category",
                    "fieldname" => "categories",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\ObjectIdSum"
                ],
                [
                    "name" => "simularity_feature",
                    "fieldname" => "features",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\ObjectIdSum"
                ],
                [
                    "name" => "simularity_technolgy",
                    "fieldname" => "technologies",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\ObjectIdSum"
                ],
                [
                    "name" => "simularity_attributes",
                    "fieldname" => "attributes",
                    "hideInFieldlistDatatype" => "true",
                    "type" => "INTEGER",
                    "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\ObjectIdSum"
                ] */
            ],
            /* configuration of different tenants */
            "tenants" => ""

            /*  "elasticsearch" => [
                    "class" => "\\OnlineShop\\Framework\\IndexService\\Config\\ElasticSearch",
                    "file" => "/website/var/plugins/EcommerceFramework/assortment-tenant-elasticsearch.php"
                ],
                "sampletenant" => [
                    "class" => "\\OnlineShop\\Framework\\IndexService\\Config\\DefaultMysqlSubTenantConfig",
                    "generalSearchColumns" => [
                        "column" => [
                            [
                                "name" => "name"
                            ],
                            [
                                "name" => "shortDescription"
                            ]
                        ]
                    ],
                    "columns" => [
                        "column" => [
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
                                "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\AssetId"
                            ],
                            [
                                "name" => "hardDiskDriveCapacity",
                                "type" => "DOUBLE",
                                "getter" => "\\OnlineShop\\Framework\\IndexService\\Getter\\DefaultBrickGetter",
                                "interpreter" => "\\OnlineShop\\Framework\\IndexService\\Interpreter\\DimensionUnitField",
                                "config" => [
                                    "brickfield" => "specialFeatures",
                                    "bricktype" => "Memory",
                                    "fieldname" => "hardDiskDriveCapacity"
                                ]
                            ]
                        ]
                    ] */
        ],
        /*  assign backend implementations and views to filter type field collections

            helper = tool for pimcore backend controller to get possible group by values for a certain field
                     (used by object data type IndexFieldSelection, e.g. in filter definitions)
         */
        "filtertypes" => [
            "helper" => "\\OnlineShop\\Framework\\FilterService\\FilterGroupHelper",
            "FilterNumberRange" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\NumberRange",
                "script" => "/shop/filters/range.php"
            ],
            "FilterNumberRangeSelection" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\NumberRangeSelection",
                "script" => "/shop/filters/numberrange.php"
            ],
            "FilterSelect" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\Select",
                "script" => "/shop/filters/select.php"
            ],
            "FilterSelectFromMultiSelect" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\SelectFromMultiSelect",
                "script" => "/shop/filters/select.php"
            ],
            "FilterMultiSelect" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\MultiSelect",
                "script" => "/shop/filters/multiselect.php"
            ],
            "FilterMultiSelectFromMultiSelect" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\MultiSelectFromMultiSelect",
                "script" => "/shop/filters/multiselect.php"
            ],
            "FilterMultiRelation" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\MultiSelectRelation",
                "script" => "/shop/filters/multiselect-relation.php"
            ],
            "FilterCategory" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\SelectCategory",
                "script" => "/shop/filters/select_category.php"
            ],
            "FilterRelation" => [
                "class" => "\\OnlineShop\\Framework\\FilterService\\FilterType\\SelectRelation",
                "script" => "/shop/filters/object_relation.php"
            ]
        ],

        /* pricing manager */
        "pricingmanager" => [
            "class" => "\\OnlineShop\\Framework\\PricingManager\\PricingManager",
            "config" => [
                /* "disabled" => true, */

                /* rule and priceinfo */
                "rule" => [
                    "class" => "\\OnlineShop\\Framework\\PricingManager\\Rule"
                ],
                "priceInfo" => [
                    "class" => "\\OnlineShop\\Framework\\PricingManager\\PriceInfo"
                ],
                /* rule conditions */
                "condition" => [
                    "Bracket" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\Bracket"
                    ],
                    "DateRange" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\DateRange"
                    ],
                    "CartAmount" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\CartAmount"
                    ],
                    "CatalogProduct" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\CatalogProduct"
                    ],
                    "CatalogCategory" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\CatalogCategory"
                    ],
                    "Token" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\Token"
                    ],
                    "VoucherToken" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\VoucherToken"
                    ],
                    "Sold" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\Sold"
                    ],
                    "Tenant" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\Tenant"
                    ],
                    "Sales" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\Sales"
                    ],
                    "ClientIp" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Condition\\ClientIp"
                    ]
                ],
                /* rule actions */
                "action" => [
                    "ProductDiscount" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Action\\ProductDiscount"
                    ],
                    "CartDiscount" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Action\\CartDiscount"
                    ],
                    "Gift" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Action\\Gift"
                    ],
                    "FreeShipping" => [
                        "class" => "\\OnlineShop\\Framework\\PricingManager\\Action\\FreeShipping"
                    ]
                ],
                //Checkout Tenants for Pricing Rules - e.g. to disable pricing rules for one tenant completely
                "tenants" => [
                    "noPricingRules" => [
                        "disabled" => "false"
                    ]
                ]
            ]
        ],
        /* Offertool */
        "offertool" => [
            "class" => "\\OnlineShop\\Framework\\OfferTool\\DefaultService",
            "orderstorage" => [
                "offerClass" => "\\Pimcore\\Model\\Object\\OfferToolOffer",
                "offerItemClass" => "\\Pimcore\\Model\\Object\\OfferToolOfferItem",
                "parentFolderPath" => "/offertool/offers/%Y/%m"
            ]
        ],
        /* order manager */
        "ordermanager" => [
            "class" => "OnlineShop\\Framework\\OrderManager\\OrderManager",
            "config" => [
                "orderList" => [
                    "class" => "OnlineShop\\Framework\\OrderManager\\Order\\Listing",
                    "classItem" => "OnlineShop\\Framework\\OrderManager\\Order\\Listing\\Item"
                ],
                "orderAgent" => [
                    "class" => "OnlineShop\\Framework\\OrderManager\\Order\\Agent"
                ],
                /* settings for order storage - pimcore class names for oder and order items */
                "orderstorage" => [
                    "orderClass" => "\\Pimcore\\Model\\Object\\OnlineShopOrder",
                    "orderItemClass" => "\\Pimcore\\Model\\Object\\OnlineShopOrderItem"
                ],
                /* parent folder for order objects - either ID or path can be specified. path is parsed by strftime. */
                "parentorderfolder" => "/order/%Y/%m/%d",
                /* special configuration for specific checkout tenants */
                "tenants" => [
                    "otherFolder" => [
                        "parentorderfolder" => "/order_otherfolder/%Y/%m/%d"
                    ]
                ]
            ]
        ],
        /* voucher service - define voucher service implementation class and map token managers */
        "voucherservice" => [
            "class" => "\\OnlineShop\\Framework\\VoucherService\\DefaultService",
            /* assign backend implementations to voucher token type field collections */
            "tokenmanagers" => [
                "VoucherTokenTypePattern" => [
                    "class" => "\\OnlineShop\\Framework\\VoucherService\\TokenManager\\Pattern"
                ],
                "VoucherTokenTypeSingle" => [
                    "class" => "\\OnlineShop\\Framework\\VoucherService\\TokenManager\\Single"
                ]
            ],
            "config" => [
                /*  Reservations older than x MINUTES get removed by maintenance task */
                "reservations" => [
                    "duration" => "5"
                ],
                /* Statistics older than x DAYS get removed by maintenance task */
                "statistics" => [
                    "duration" => "30"
                ]
            ]
        ],
        
        /*  tracking manager - define which trackers (e.g. Google Analytics Universal Ecommerce) are active and should
     be called when you track something via TrackingManager */
        "trackingmanager" => [
            "class" => "OnlineShop\\Framework\\Tracking\\TrackingManager",
            "config" => [
                "trackers" => [
                    "tracker" => [
                        [
                        "name" => "GoogleAnalyticsEnhancedEcommerce",
                        "class" => "OnlineShop\\Framework\\Tracking\\Tracker\\Analytics\\EnhancedEcommerce",
                        "trackingItemBuilder" => "\\OnlineShop\\Framework\\Tracking\\TrackingItemBuilder"
                        ]
                    ]
                ]
            ]
        ],
        
        /* pimcore OnlineShop Menu */
        "pimcore" => [
            "menu" => [
                "pricingRules" => [
                    "disabled" => "0"
                ],
                "orderlist" => [
                    "disabled" => "0",
                    "route" => "/admin/ecommerceframework/admin-order/list"
                ]
            ]
        ]
    ]
];
