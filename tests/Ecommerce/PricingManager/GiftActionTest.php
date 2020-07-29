<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 21.11.2018
 * Time: 09:47
 */

namespace Pimcore\Tests\Ecommerce\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\Gift;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\CartAmount;
use Pimcore\Tests\Ecommerce\PricingManager\Rule\AbstractRuleTest;

class GiftActionTest extends AbstractRuleTest
{
    protected $productDefinitions1 = [
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
                'price' => 40,
            ],
        ],

    ];

    protected $tests1 = [
        'productPriceSingle' => 100,
        'productPriceTotal' => 200,
        'cartSubTotal' => 140,
        'cartGrandTotal' => 140,
        'cartSubTotalModificators' => 140,
        'cartGrandTotalModificators' => 150,
        'giftItemCount' => 0,
    ];

    protected $productDefinitions2 = [
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
                'price' => 40,
            ],
            [
                'id' => 6,
                'price' => 80,
            ],
        ],
    ];

    public function testOneGift()
    {
        $pricingManager = $this->buildPricingManager([]);
        $gift1 = $this->setUpProduct(777, 100, $pricingManager);

        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => Gift::class,
                        'product' => $gift1,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 200,
                ],
            ],
        ];

        $this->doAssertionsWithShippingCosts($ruleDefinitions, $this->productDefinitions1, $this->tests1, false);

        $tests = [
            'productPriceSingle' => 100,
            'productPriceTotal' => 200,
            'cartSubTotal' => 220,
            'cartGrandTotal' => 220,
            'cartSubTotalModificators' => 220,
            'cartGrandTotalModificators' => 230,
            'giftItemCount' => 1,
        ];

        $this->doAssertionsWithShippingCosts($ruleDefinitions, $this->productDefinitions2, $tests, false);
    }

    public function testMultipleGifts()
    {
        $pricingManager = $this->buildPricingManager([]);
        $gift1 = $this->setUpProduct(777, 100, $pricingManager);
        $gift2 = $this->setUpProduct(888, 200, $pricingManager);

        $ruleDefinitions = [
            'testrule' => [
                'actions' => [
                    [
                        'class' => Gift::class,
                        'product' => $gift1,
                    ],
                    [
                        'class' => Gift::class,
                        'product' => $gift2,
                    ],
                ],
                'condition' => [
                    'class' => CartAmount::class,
                    'limit' => 200,
                ],
            ],
        ];

        $this->doAssertionsWithShippingCosts($ruleDefinitions, $this->productDefinitions1, $this->tests1, false);

        $tests = [
            'productPriceSingle' => 100,
            'productPriceTotal' => 200,
            'cartSubTotal' => 220,
            'cartGrandTotal' => 220,
            'cartSubTotalModificators' => 220,
            'cartGrandTotalModificators' => 230,
            'giftItemCount' => 2,
        ];

        $this->doAssertionsWithShippingCosts($ruleDefinitions, $this->productDefinitions2, $tests, false);
    }
}
