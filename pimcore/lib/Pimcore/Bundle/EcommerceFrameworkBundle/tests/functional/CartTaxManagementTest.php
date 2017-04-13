<?php
namespace EcommerceFramework;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Shipping;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Model\Object\OnlineShopTaxClass;

class CartTaxManagementTest extends \Codeception\Test\Unit
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

    private function buildTaxClass($taxes = [], $combinationType = TaxEntry::CALCULATION_MODE_COMBINE)
    {
        $taxClass = new OnlineShopTaxClass();
        $taxClass->setId(md5(serialize($taxes)));
        $taxEntries = new \Pimcore\Model\Object\Fieldcollection();

        foreach ($taxes as $name => $tax) {
            $entry = new \Pimcore\Model\Object\Fieldcollection\Data\TaxEntry();
            $entry->setPercent($tax);
            $entry->setName($name);
            $taxEntries->add($entry);
        }
        $taxClass->setTaxEntries($taxEntries);
        $taxClass->setTaxEntryCombinationType($combinationType);

        return $taxClass;
    }

    private function setUpProduct($grossPrice, $taxes = [], $combinationType = TaxEntry::CALCULATION_MODE_COMBINE)
    {
        $taxClass = $this->buildTaxClass($taxes, $combinationType);

        $config = new \stdClass();

        $priceSystem = Stub::construct(AttributePriceSystem::class, [$config], [
            'getTaxClassForProduct' => function () use ($taxClass) {
                return $taxClass;
            },
            'getTaxClassForPriceModification' => function () use ($taxClass) {
                return $taxClass;
            },
            'getPriceClassInstance' => function ($amount) {
                return new Price($amount, new Currency('EUR'));
            },
            'calculateAmount' => function () use ($grossPrice) {
                return $grossPrice;
            }
        ]);

        return Stub::construct(AbstractProduct::class, [], [
            'getId' => function () {
                return rand();
            },
            'getPriceSystemImplementation' => function () use ($priceSystem) {
                return $priceSystem;
            },
            'getCategories' => function () {
                return [];
            }
        ]);
    }

    /**
     * @return SessionCart
     */
    private function setUpCart()
    {
        return Stub::construct('\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\SessionCart', [], [
            'getSession' => function () {
                return [];
            },
            'isCartReadOnly' => function () {
                return false;
            },
            'modified' => function () {
            }
        ]);
    }

    /**
     * @param ICart $cart
     *
     * @return CartPriceCalculator
     */
    private function setUpCartCalculator(ICart $cart, $withModificators = false, $taxes = [])
    {
        $config = new \stdClass();

        $calculator = new CartPriceCalculator($config, $cart);

        if ($withModificators) {
            $shipping = new Shipping();
            $shipping->setCharge(10);
            $shipping->setTaxClass($this->buildTaxClass($taxes));
            $calculator->addModificator($shipping);
        }

        return $calculator;
    }

    // tests
    public function testCartWithoutTaxEntries()
    {
        $product = $this->setUpProduct(100);
        $product2 = $this->setUpProduct(50);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart);
        $subTotal = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertEquals(250, $subTotal->getGrossAmount(), 'subtotal gross');
        $this->assertEquals(250, $subTotal->getNetAmount(), 'subtotal net');

        $this->assertEquals(250, $grandTotal->getGrossAmount(), 'grandtotal gross');
        $this->assertEquals(250, $grandTotal->getNetAmount(), 'grandtotal net');
    }

    public function testCartWithTaxEntriesCombine()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_COMBINE);
        $product2 = $this->setUpProduct(50, [1 => 10], TaxEntry::CALCULATION_MODE_COMBINE);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart);
        $subTotal = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertEquals(250, round($subTotal->getGrossAmount(), 2), 'subtotal gross');
        $this->assertEquals(205.45, round($subTotal->getNetAmount(), 2), 'subtotal net');
        $taxEntries = $subTotal->getTaxEntries();

        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'subtotal taxentry 1 percent');
        $this->assertEquals(20.55, round($taxEntries['1-10']->getAmount(), 2), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'subtotal taxentry 2 percent');
        $this->assertEquals(24, round($taxEntries['2-15']->getAmount(), 2), 'subtotal taxentry 2 amount');

        $this->assertEquals(250, round($grandTotal->getGrossAmount(), 2), 'grandtotal gross');
        $this->assertEquals(205.45, round($grandTotal->getNetAmount(), 2), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();
        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'grandtotal taxentry 1 percent');
        $this->assertEquals(20.55, round($taxEntries['1-10']->getAmount(), 2), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'grandtotal taxentry 2 percent');
        $this->assertEquals(24, round($taxEntries['2-15']->getAmount(), 2), 'grandtotal taxentry 2 amount');
    }

    public function testPriceSystemWithTaxEntriesOneAfterAnother()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);
        $product2 = $this->setUpProduct(50, [1 => 10], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart);
        $subTotal = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertEquals(250, round($subTotal->getGrossAmount(), 2), 'subtotal gross');
        $this->assertEquals(203.56, round($subTotal->getNetAmount(), 2), 'subtotal net');
        $taxEntries = $subTotal->getTaxEntries();
        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'subtotal taxentry 1 percent');
        $this->assertEquals(20.36, round($taxEntries['1-10']->getAmount(), 2), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'subtotal taxentry 2 percent');
        $this->assertEquals(26.09, round($taxEntries['2-15']->getAmount(), 2), 'subtotal taxentry 2 amount');

        $this->assertEquals(250, round($grandTotal->getGrossAmount(), 2), 'grandtotal gross');
        $this->assertEquals(203.56, round($grandTotal->getNetAmount(), 2), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();
        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'grandtotal taxentry 1 percent');
        $this->assertEquals(20.36, round($taxEntries['1-10']->getAmount(), 2), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'grandtotal taxentry 2 percent');
        $this->assertEquals(26.09, round($taxEntries['2-15']->getAmount(), 2), 'grandtotal taxentry 2 amount');
    }

    public function testCartWithoutTaxEntriesWithModificators()
    {
        $product = $this->setUpProduct(100);
        $product2 = $this->setUpProduct(50);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart, true);
        $subTotal = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertEquals(250, $subTotal->getGrossAmount(), 'subtotal gross');
        $this->assertEquals(250, $subTotal->getNetAmount(), 'subtotal net');

        $this->assertEquals(260, $grandTotal->getGrossAmount(), 'grandtotal gross');
        $this->assertEquals(260, $grandTotal->getNetAmount(), 'grandtotal net');
    }

    public function testCartWithTaxEntriesCombineWithModificators()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_COMBINE);
        $product2 = $this->setUpProduct(50, [1 => 10], TaxEntry::CALCULATION_MODE_COMBINE);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart, true, ['shipping' => 20]);
        $subTotal = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertEquals(250, round($subTotal->getGrossAmount(), 2), 'subtotal gross');
        $this->assertEquals(205.45, round($subTotal->getNetAmount(), 2), 'subtotal net');
        $taxEntries = $subTotal->getTaxEntries();
        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'subtotal taxentry 1 percent');
        $this->assertEquals(20.55, round($taxEntries['1-10']->getAmount(), 2), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'subtotal taxentry 2 percent');
        $this->assertEquals(24, round($taxEntries['2-15']->getAmount(), 2), 'subtotal taxentry 2 amount');

        $this->assertEquals(260, round($grandTotal->getGrossAmount(), 2), 'grandtotal gross');
        $this->assertEquals(213.79, round($grandTotal->getNetAmount(), 2), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();

        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'grandtotal taxentry 1 percent');
        $this->assertEquals(20.55, round($taxEntries['1-10']->getAmount(), 2), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'grandtotal taxentry 2 percent');
        $this->assertEquals(24, round($taxEntries['2-15']->getAmount(), 2), 'grandtotal taxentry 2 amount');
        $this->assertEquals(20, round($taxEntries['shipping-20']->getPercent(), 2), 'grandtotal taxentry 3 percent');
        $this->assertEquals(1.67, round($taxEntries['shipping-20']->getAmount(), 2), 'grandtotal taxentry 3 amount');
    }

    public function testPriceSystemWithTaxEntriesOneAfterAnotherWithModificators()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);
        $product2 = $this->setUpProduct(50, [1 => 10], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart, true, ['shipping' => 20]);
        $subTotal = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertEquals(250, round($subTotal->getGrossAmount(), 2), 'subtotal gross');
        $this->assertEquals(203.56, round($subTotal->getNetAmount(), 2), 'subtotal net');
        $taxEntries = $subTotal->getTaxEntries();
        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'subtotal taxentry 1 percent');
        $this->assertEquals(20.36, round($taxEntries['1-10']->getAmount(), 2), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'subtotal taxentry 2 percent');
        $this->assertEquals(26.09, round($taxEntries['2-15']->getAmount(), 2), 'subtotal taxentry 2 amount');

        $this->assertEquals(260, round($grandTotal->getGrossAmount(), 2), 'grandtotal gross');
        $this->assertEquals(211.89, round($grandTotal->getNetAmount(), 2), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();

        $this->assertEquals(10, round($taxEntries['1-10']->getPercent(), 2), 'grandtotal taxentry 1 percent');
        $this->assertEquals(20.36, round($taxEntries['1-10']->getAmount(), 2), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, round($taxEntries['2-15']->getPercent(), 2), 'grandtotal taxentry 2 percent');
        $this->assertEquals(26.09, round($taxEntries['2-15']->getAmount(), 2), 'grandtotal taxentry 2 amount');

        $this->assertEquals(20, round($taxEntries['shipping-20']->getPercent(), 2), 'grandtotal taxentry 3 percent');
        $this->assertEquals(1.67, round($taxEntries['shipping-20']->getAmount(), 2), 'grandtotal taxentry 3 amount');
    }
}
