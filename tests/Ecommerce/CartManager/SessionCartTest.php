<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Ecommerce\CartManager;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Tests\Test\TestCase;

class SessionCartTest extends TestCase
{
    /**
     * @var \EcommerceFramework\UnitTester
     */
    protected $tester;

    // tests

    /**
     * @return SessionCart
     */
    protected function buildCart()
    {
        $cart = Stub::construct('\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\SessionCart', [], [
            'getSession' => function () {
                return [];
            },
            'isCartReadOnly' => function () {
                return false;
            },
            'modified' => function () {
            },
        ]);

        return $cart;
    }

    /**
     * @param int $id
     *
     * @return AbstractProduct
     */
    protected function buildProduct(int $id)
    {
        $product = $this->getMockBuilder(AbstractProduct::class)->getMock();
        $product->method('getId')->willReturn($id);

        return $product;
    }

    public function testAddToCart()
    {
        $product = $this->buildProduct(5);
        $cart = $this->buildCart();

        $cart->addItem($product, 2);
        $items = $cart->getItems();

        $this->assertEquals(1, count($items), 'item count');
        $this->assertEquals(2, $cart->getItemAmount(), 'item amount');
        $this->assertEquals(1, $cart->getItemCount(), 'item count with cart method');
    }

    public function testCartAmountAndCount()
    {
        $product1 = $this->buildProduct(5);
        $product2 = $this->buildProduct(6);
        $product3 = $this->buildProduct(7);
        $product4 = $this->buildProduct(8);

        $cart = $this->buildCart();

        $subEntry1 = new AbstractSetProductEntry($product2, 1);
        $subEntry2 = new AbstractSetProductEntry($product3, 2);

        $cart->addItem($product1, 2, null, false, [], [$subEntry1, $subEntry2]);
        $cart->addItem($product4, 6);
        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'main item count from getItems');

        //test default value = main items only
        $this->assertEquals(8, $cart->getItemAmount(), 'item amount - default mode');
        $this->assertEquals(2, $cart->getItemCount(), 'item count with cart method - default mode');

        //test COUNT_MAIN_ITEMS_ONLY
        $this->assertEquals(8, $cart->getItemAmount(CartInterface::COUNT_MAIN_ITEMS_ONLY), 'item amount - mode `COUNT_MAIN_ITEMS_ONLY`');
        $this->assertEquals(2, $cart->getItemCount(CartInterface::COUNT_MAIN_ITEMS_ONLY), 'item count with cart method - mode `COUNT_MAIN_ITEMS_ONLY`');

        //test COUNT_MAIN_AND_SUB_ITEMS
        $this->assertEquals(14, $cart->getItemAmount(CartInterface::COUNT_MAIN_AND_SUB_ITEMS), 'item amount - mode `COUNT_MAIN_AND_SUB_ITEMS`');
        $this->assertEquals(4, $cart->getItemCount(CartInterface::COUNT_MAIN_AND_SUB_ITEMS), 'item count with cart method - mode `COUNT_MAIN_AND_SUB_ITEMS`');

        //test COUNT_MAIN_OR_SUB_ITEMS
        $this->assertEquals(12, $cart->getItemAmount(CartInterface::COUNT_MAIN_OR_SUB_ITEMS), 'item amount - mode `COUNT_MAIN_OR_SUB_ITEMS`');
        $this->assertEquals(3, $cart->getItemCount(CartInterface::COUNT_MAIN_OR_SUB_ITEMS), 'item count with cart method - mode `COUNT_MAIN_OR_SUB_ITEMS`');
    }
}
