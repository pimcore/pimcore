<?php
namespace EcommerceFramework;

use Codeception\Util\Stub;
use OnlineShop\Framework\CartManager\SessionCart;
use OnlineShop\Framework\Model\AbstractProduct;

class SessionCartTest extends \Codeception\Test\Unit
{
    /**
     * @var \EcommerceFramework\UnitTester
     */
    protected $tester;

    protected function _before()
    {

    }

    protected function _after()
    {
    }
 
    // tests


    public function testAddToCart()
    {

        /**
         * @var $product AbstractProduct
         */
        $product = $this->getMock(AbstractProduct::class);
        $product->method("getId")->willReturn(5);

        /**
         * @var $cart SessionCart
         */
        $cart = Stub::construct("OnlineShop\\Framework\\CartManager\\SessionCart", [], [
            "getSession" => function() {
                return [];
            },
            "isCartReadOnly" => function() {
                return false;
            },
            "modified" => function() {}
        ]);

        $cart->addItem($product, 2);
        $items = $cart->getItems();

        $this->assertEquals(count($items), 1, "item count");
        $this->assertEquals($cart->getItemAmount(), 2, "item amount");

    }
}