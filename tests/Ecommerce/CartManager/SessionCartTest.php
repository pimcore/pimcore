<?php

namespace Pimcore\Tests\Ecommerce\CartManager;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
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
    protected function buildCart() {
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
     * @return AbstractProduct
     */
    protected function buildProduct(int $id) {
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

        $this->assertEquals(count($items), 1, 'item count');
        $this->assertEquals($cart->getItemAmount(), 2, 'item amount');
        $this->assertEquals($cart->getItemCount(), 1, 'item count with cart method');
    }

    public function testCartAmountAndCount() {
        $product1 = $this->buildProduct(5);
        $product2 = $this->buildProduct(6);
        $product3 = $this->buildProduct(7);
        $product4 = $this->buildProduct(8);

        $cart = $this->buildCart();

        $cart->addItem($product1, 2, null, false, [], [$product2, $product3]);
        $cart->addItem($product4, 6);
        $items = $cart->getItems();

        $this->assertEquals(count($items), 2, 'main item count from getItems');

        //test default value = main items only
        $this->assertEquals($cart->getItemAmount(), 8, 'item amount - default mode');
        $this->assertEquals($cart->getItemCount(), 2, 'item count with cart method - default mode');

        //test legacy value false = main items only
        //TODO remove in Pimcore 10.0.0
        $this->assertEquals($cart->getItemAmount(false), 8, 'item amount - legacy mode `false`');
        $this->assertEquals($cart->getItemCount(false), 2, 'item count with cart method - legacy mode `false`');

        //test legacy value true = consider sub items somehow
        //TODO remove in Pimcore 10.0.0
        $this->assertEquals($cart->getItemAmount(true), 10, 'item amount - legacy mode `true`');
        $this->assertEquals($cart->getItemCount(true), 4, 'item count with cart method - legacy mode `true`');


        //test COUNT_MAIN_ITEMS_ONLY
        $this->assertEquals($cart->getItemAmount(CartInterface::COUNT_MAIN_ITEMS_ONLY), 8, 'item amount - mode `COUNT_MAIN_ITEMS_ONLY`');
        $this->assertEquals($cart->getItemCount(CartInterface::COUNT_MAIN_ITEMS_ONLY), 2, 'item count with cart method - mode `COUNT_MAIN_ITEMS_ONLY`');

        //test COUNT_MAIN_AND_SUB_ITEMS
        $this->assertEquals($cart->getItemAmount(CartInterface::COUNT_MAIN_AND_SUB_ITEMS), 12, 'item amount - mode `COUNT_MAIN_AND_SUB_ITEMS`');
        $this->assertEquals($cart->getItemCount(CartInterface::COUNT_MAIN_AND_SUB_ITEMS), 4, 'item count with cart method - mode `COUNT_MAIN_AND_SUB_ITEMS`');

        //test COUNT_MAIN_OR_SUB_ITEMS
        $this->assertEquals($cart->getItemAmount(CartInterface::COUNT_MAIN_OR_SUB_ITEMS), 10, 'item amount - mode `COUNT_MAIN_OR_SUB_ITEMS`');
        $this->assertEquals($cart->getItemCount(CartInterface::COUNT_MAIN_OR_SUB_ITEMS), 3, 'item count with cart method - mode `COUNT_MAIN_OR_SUB_ITEMS`');
    }


}
