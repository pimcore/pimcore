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
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\Bracket;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartAmount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CatalogProduct;
use Pimcore\Tests\Ecommerce\PricingManager\Rule\AbstractRuleTest;

class CombinedRuleTest extends AbstractRuleTest
{
    public function testSimpleProductAndCartDiscount()
    {
        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => '',
            ],
            'testrule2' => [
                'actions' => [
                    [
                        'class' => CartDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => '',
            ],
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],

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

    public function testSimpleProductAndCartDiscountWithCondition1()
    {
        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => '',
            ],
            'testrule2' => [
                'actions' => [
                    [
                        'class' => CartDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 200,
                ],
            ],
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],

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
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
                [
                    'id' => 6,
                    'price' => 80,
                ],
            ],

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

    public function testSimpleProductAndCartDiscountWithConditionFreeShippingFirst()
    {
        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => '',
            ],
            'testrule3' => [
                'actions' => [
                    [
                        'class' => FreeShipping::class,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 200,
                ],
            ],
            'testrule4' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 10,
                ],
            ],
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],

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
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
                [
                    'id' => 6,
                    'price' => 80,
                ],
            ],

        ];

        $tests = [
            'productPriceSingle' => 90,
            'productPriceTotal' => 180,
            'cartSubTotal' => 200,
            'cartGrandTotal' => 200,
            'cartSubTotalModificators' => 200,
            'cartGrandTotalModificators' => 200,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
    }

    public function testSimpleProductAndCartDiscountWithConditionFreeShippingLast()
    {
        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => '',
            ],
            'testrule4' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 10,
                ],
            ],
            'testrule3' => [
                'actions' => [
                    [
                        'class' => FreeShipping::class,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 200,
                ],
            ],
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],

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
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
                [
                    'id' => 6,
                    'price' => 80,
                ],
            ],

        ];

        $tests = [
            'productPriceSingle' => 90,
            'productPriceTotal' => 180,
            'cartSubTotal' => 200,
            'cartGrandTotal' => 200,
            'cartSubTotalModificators' => 200,
            'cartGrandTotalModificators' => 200,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
    }

    public function testTwoAndConditions()
    {
        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => [
                    'class' => Bracket::class,
                    'conditions' => [
                        [
                            'condition' => [
                                'class' => CatalogProduct::class,
                                'products' => [$this->mockProductForCondition(5)],
                            ],
                            'operator' => Bracket::OPERATOR_AND,
                        ],
                        [
                            'condition' => [
                                'class' => CatalogProduct::class,
                                'products' => [$this->mockProductForCondition(4)],
                            ],
                            'operator' => Bracket::OPERATOR_AND,
                        ],
                    ],
                ],

            ],
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],
        ];

        $tests = [
            'productPriceSingle' => 100,
            'productPriceTotal' => 200,
            'cartSubTotal' => 150,
            'cartGrandTotal' => 150,
            'cartSubTotalModificators' => 150,
            'cartGrandTotalModificators' => 160,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);

        $productDefinitions = [
            'singleProduct' => [
                'id' => 5,
                'price' => 50,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],
        ];

        $tests = [
            'productPriceSingle' => 50,
            'productPriceTotal' => 100,
            'cartSubTotal' => 150,
            'cartGrandTotal' => 150,
            'cartSubTotalModificators' => 150,
            'cartGrandTotalModificators' => 160,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
    }

    public function testTwoOrConditions()
    {
        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => ProductDiscount::class,
                        'amount' => 10,
                    ],
                ],
                'condition' => [
                    'class' => Bracket::class,
                    'conditions' => [
                        [
                            'condition' => [
                                'class' => CatalogProduct::class,
                                'products' => [$this->mockProductForCondition(5)],
                            ],
                            'operator' => Bracket::OPERATOR_AND,
                        ],
                        [
                            'condition' => [
                                'class' => CatalogProduct::class,
                                'products' => [$this->mockProductForCondition(4)],
                            ],
                            'operator' => Bracket::OPERATOR_OR,
                        ],
                    ],
                ],

            ],
        ];

        $productDefinitions = [
            'singleProduct' => [
                'id' => 4,
                'price' => 100,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],
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
                'id' => 5,
                'price' => 50,
            ],
            'cart' => [
                [
                    'id' => 4,
                    'price' => 100,
                ],
                [
                    'id' => 5,
                    'price' => 50,
                ],
            ],
        ];

        $tests = [
            'productPriceSingle' => 40,
            'productPriceTotal' => 80,
            'cartSubTotal' => 130,
            'cartGrandTotal' => 130,
            'cartSubTotalModificators' => 130,
            'cartGrandTotalModificators' => 140,
        ];

        $this->doAssertions($ruleDefinitions, $productDefinitions, $tests);
    }
}
