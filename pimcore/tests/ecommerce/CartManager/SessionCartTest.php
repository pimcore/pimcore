<?php

namespace Pimcore\Tests\Ecommerce\CartManager;

use Codeception\Util\Stub;
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

    public function testAddToCart()
    {
        $product = $this->getMockBuilder(AbstractProduct::class)->getMock();
        $product->method('getId')->willReturn(5);

        /**
         * @var $cart    SessionCart
         * @var $product AbstractProduct
         */
        $cart = Stub::construct('\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\SessionCart', [], [
            'getSession'     => function () {
                return [];
            },
            'isCartReadOnly' => function () {
                return false;
            },
            'modified'       => function () {
            }
        ]);

        $cart->addItem($product, 2);
        $items = $cart->getItems();

        $this->assertEquals(count($items), 1, 'item count');
        $this->assertEquals($cart->getItemAmount(), 2, 'item amount');
    }
}
