<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 29.03.2018
 * Time: 11:17
 */

namespace Pimcore\Tests\Ecommerce\PricingManager;


use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\CartDiscount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\FreeShipping;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\Gift;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartAmount;
use Pimcore\Tests\Ecommerce\PricingManager\Rule\AbstractRuleTest;


class CombinedRuleTest extends AbstractRuleTest
{


    public function testSimpleProductAndCartDiscount() {

        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10
                    ]
                ],
                'condition' => ''
            ],
            'testrule2' => [
                'actions' => [
                    [
                        'class' => CartDiscount::class,
                        'amount' => 10
                    ]
                ],
                'condition' => ''
            ]
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100
                ],
                [
                    'id' => 5,
                    'price' => 50
                ]
            ]

        ];

        $tests = [
            'productPriceSingle' => 90,
            'productPriceTotal' => 180,
            'cartSubTotal' => 130,
            'cartGrandTotal' => 120,
            'cartSubTotalModificators' => 130,
            'cartGrandTotalModificators' => 130,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
    }


    public function testSimpleProductAndCartDiscountWithCondition1() {

        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10
                    ]
                ],
                'condition' => ''
            ],
            'testrule2' => [
                'actions' => [
                    [
                        'class' => CartDiscount::class,
                        'amount' => 10
                    ]
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 200
                ]
            ]
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100
                ],
                [
                    'id' => 5,
                    'price' => 50
                ]
            ]

        ];

        $tests = [
            'productPriceSingle' => 90,
            'productPriceTotal' => 180,
            'cartSubTotal' => 130,
            'cartGrandTotal' => 130,
            'cartSubTotalModificators' => 130,
            'cartGrandTotalModificators' => 140,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);


        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100
                ],
                [
                    'id' => 5,
                    'price' => 50
                ],
                [
                    'id' => 6,
                    'price' => 80
                ]
            ]

        ];

        $tests = [
            'productPriceSingle' => 90,
            'productPriceTotal' => 180,
            'cartSubTotal' => 200,
            'cartGrandTotal' => 190,
            'cartSubTotalModificators' => 200,
            'cartGrandTotalModificators' => 200,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
    }


//    public function testSimpleProductAndCartDiscountWithCondition2() {
//
//        $ruleDefinitions = [
//            'testrule' => [
//                'actions' => [
//                    [
//                        'class' => ProductDiscount::class,
//                        'amount' => 10
//                    ]
//                ],
//                'condition' => [
//                    'class' => CartAmount::class,
//                    'limit' => 200,
//                    'mode' => CartAmount::CALCULATION_MODE_PRODUCT_AND_CART
//                ]
//            ],
////            'testrule2' => [
////                'actions' => [
////                    [
////                        'class' => CartDiscount::class,
////                        'amount' => 10
////                    ]
////                ],
////                'condition' => [
////                    'class' => CartAmount::class,
////                    'limit' => 200
////                ]
////            ]
//        ];
//
//        $productDefinitions = [
//            'singleProduct' => [
//                'id' => 4,
//                'price' => 100
//            ],
//            'cart' => [
//                [
//                    'id' => 4,
//                    'price' => 100
//                ],
//                [
//                    'id' => 5,
//                    'price' => 50
//                ]
//            ]
//
//        ];
//
//        $tests = [
//            'productPriceSingle' => 100,
//            'productPriceTotal' => 200,
//            'cartSubTotal' => 150,
//            'cartGrandTotal' => 150,
//            'cartSubTotalModificators' => 150,
//            'cartGrandTotalModificators' => 160,
//        ];
//
//        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
//
//
//        $productDefinitions = [
//            'singleProduct' => [
//                'id' => 4,
//                'price' => 100
//            ],
//            'cart' => [
//                [
//                    'id' => 4,
//                    'price' => 100
//                ],
//                [
//                    'id' => 5,
//                    'price' => 50
//                ],
//                [
//                    'id' => 6,
//                    'price' => 80
//                ]
//            ]
//
//        ];
//
//        $tests = [
//            'productPriceSingle' => 100,
//            'productPriceTotal' => 200,
//            'cartSubTotal' => 200,
//            'cartGrandTotal' => 200,
//            'cartSubTotalModificators' => 200,
//            'cartGrandTotalModificators' => 210,
//        ];
//
//        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
//    }


}