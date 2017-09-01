<?php

declare(strict_types=1);

namespace Pimcore\Tests\Ecommerce;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceCalculator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Shipping;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\TaxEntry as TaxEntryFieldcollection;
use Pimcore\Model\DataObject\OnlineShopTaxClass;
use Pimcore\Tests\Test\EcommerceTestCase;

class CartTaxManagementTest extends EcommerceTestCase
{
    private function buildTaxClass(array $taxes = [], $combinationType = TaxEntry::CALCULATION_MODE_COMBINE)
    {
        $taxClass = new OnlineShopTaxClass();
        $taxClass->setId(md5(serialize($taxes)));

        $taxEntries = new Fieldcollection();
        foreach ($taxes as $name => $tax) {
            $entry = new TaxEntryFieldcollection();
            $entry->setPercent($tax);
            $entry->setName($name);
            $taxEntries->add($entry);
        }

        $taxClass->setTaxEntries($taxEntries);
        $taxClass->setTaxEntryCombinationType($combinationType);

        return $taxClass;
    }

    private function setUpProduct($grossPrice, array $taxes = [], string $combinationType = TaxEntry::CALCULATION_MODE_COMBINE): ICheckoutable
    {
        $taxClass = $this->buildTaxClass($taxes, $combinationType);

        $pricingManager = new PricingManager([], [], $this->buildSession());

        $priceSystem = Stub::construct(AttributePriceSystem::class, [$pricingManager, $this->buildEnvironment()], [
            'getTaxClassForProduct'           => function () use ($taxClass) {
                return $taxClass;
            },
            'getTaxClassForPriceModification' => function () use ($taxClass) {
                return $taxClass;
            },
            'getPriceClassInstance' => function ($amount) {
                return new Price($amount, new Currency('EUR'));
            },
            'calculateAmount' => function () use ($grossPrice) {
                return Decimal::create($grossPrice);
            }
        ]);

        /** @var Stub|ICheckoutable $product */
        $product = Stub::construct(AbstractProduct::class, [], [
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

        return $product;
    }

    /**
     * @return SessionCart
     */
    private function setUpCart()
    {
        $sessionBag = $this->buildSession()->getBag(SessionConfigurator::ATTRIBUTE_BAG_CART);

        /** @var SessionCart|\PHPUnit_Framework_MockObject_Stub $cart */
        $cart = Stub::construct(SessionCart::class, [], [
            'getSessionBag' => function () use ($sessionBag) {
                return $sessionBag;
            },
            'isCartReadOnly' => function () {
                return false;
            },
            'modified' => function () {
            }
        ]);

        return $cart;
    }

    /**
     * @param ICart $cart
     *
     * @return CartPriceCalculator
     */
    private function setUpCartCalculator(ICart $cart, $withModificators = false, $taxes = [])
    {
        $calculator = new CartPriceCalculator($this->buildEnvironment(), $cart);

        if ($withModificators) {
            $shipping = new Shipping(['charge' => 10]);
            $shipping->setTaxClass($this->buildTaxClass($taxes));
            $calculator->addModificator($shipping);
        }

        return $calculator;
    }

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

        $this->assertEquals(250, $subTotal->getGrossAmount()->asNumeric(), 'subtotal gross');
        $this->assertEquals(250, $subTotal->getNetAmount()->asNumeric(), 'subtotal net');

        $this->assertEquals(250, $grandTotal->getGrossAmount()->asNumeric(), 'grandtotal gross');
        $this->assertEquals(250, $grandTotal->getNetAmount()->asNumeric(), 'grandtotal net');
    }

    public function testCartWithTaxEntriesCombine()
    {
        $product = $this->setUpProduct(100, [
            1 => 10,
            2 => 15
        ], TaxEntry::CALCULATION_MODE_COMBINE);

        $product2 = $this->setUpProduct(50, [
            1 => 10
        ], TaxEntry::CALCULATION_MODE_COMBINE);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart);
        $subTotal   = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertSame('250.0000', $subTotal->getGrossAmount()->asString(), 'subtotal gross');
        $this->assertSame('205.4545', $subTotal->getNetAmount()->asString(), 'subtotal net');

        $taxEntries = $subTotal->getTaxEntries();

        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'subtotal taxentry 1 percent');
        $this->assertSame('20.5455', $taxEntries['1-10']->getAmount()->asString(), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'subtotal taxentry 2 percent');
        $this->assertSame('24.0000', $taxEntries['2-15']->getAmount()->asString(), 'subtotal taxentry 2 amount');

        $this->assertSame('250.0000', $grandTotal->getGrossAmount()->asString(), 'grandtotal gross');
        $this->assertSame('205.4545', $grandTotal->getNetAmount()->asString(), 'grandtotal net');

        $taxEntries = $grandTotal->getTaxEntries();

        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'grandtotal taxentry 1 percent');
        $this->assertSame('20.5455', $taxEntries['1-10']->getAmount()->asString(), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'grandtotal taxentry 2 percent');
        $this->assertSame('24.0000', $taxEntries['2-15']->getAmount()->asString(), 'grandtotal taxentry 2 amount');
    }

    public function testPriceSystemWithTaxEntriesOneAfterAnother()
    {
        $product = $this->setUpProduct(100, [
            1 => 10,
            2 => 15
        ], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        $product2 = $this->setUpProduct(50, [
            1 => 10
        ], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        $cart = $this->setUpCart();
        $cart->addItem($product, 2);
        $cart->addItem($product2, 1);

        $items = $cart->getItems();

        $this->assertEquals(2, count($items), 'item count');
        $this->assertEquals(3, $cart->getItemAmount(), 'item amount');

        $calculator = $this->setUpCartCalculator($cart);

        $subTotal   = $calculator->getSubTotal();
        $grandTotal = $calculator->getGrandTotal();

        $this->assertSame('250.0000', $subTotal->getGrossAmount()->asString(), 'subtotal gross');
        $this->assertSame('203.5572', $subTotal->getNetAmount()->asString(), 'subtotal net');

        $taxEntries = $subTotal->getTaxEntries();
        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'subtotal taxentry 1 percent');
        $this->assertSame('20.3558', $taxEntries['1-10']->getAmount()->asString(), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'subtotal taxentry 2 percent');
        $this->assertSame('26.0870', $taxEntries['2-15']->getAmount()->asString(), 'subtotal taxentry 2 amount');

        $this->assertSame('250.0000', $grandTotal->getGrossAmount()->asString(), 'grandtotal gross');
        $this->assertSame('203.5572', $grandTotal->getNetAmount()->asString(), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();
        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'grandtotal taxentry 1 percent');
        $this->assertSame('20.3558', $taxEntries['1-10']->getAmount()->asString(), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'grandtotal taxentry 2 percent');
        $this->assertSame('26.0870', $taxEntries['2-15']->getAmount()->asString(), 'grandtotal taxentry 2 amount');
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

        $this->assertEquals(250, $subTotal->getGrossAmount()->asNumeric(), 'subtotal gross');
        $this->assertEquals(250, $subTotal->getNetAmount()->asNumeric(), 'subtotal net');

        $this->assertEquals(260, $grandTotal->getGrossAmount()->asNumeric(), 'grandtotal gross');
        $this->assertEquals(260, $grandTotal->getNetAmount()->asNumeric(), 'grandtotal net');
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

        $this->assertSame('250.0000', $subTotal->getGrossAmount()->asString(), 'subtotal gross');
        $this->assertSame('205.4545', $subTotal->getNetAmount()->asString(), 'subtotal net');
        $taxEntries = $subTotal->getTaxEntries();
        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'subtotal taxentry 1 percent');
        $this->assertSame('20.5455', $taxEntries['1-10']->getAmount()->asString(), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'subtotal taxentry 2 percent');
        $this->assertSame('24.0000', $taxEntries['2-15']->getAmount()->asString(), 'subtotal taxentry 2 amount');

        $this->assertSame('260.0000', $grandTotal->getGrossAmount()->asString(), 'grandtotal gross');
        $this->assertSame('213.7878', $grandTotal->getNetAmount()->asString(), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();

        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'grandtotal taxentry 1 percent');
        $this->assertSame('20.5455', $taxEntries['1-10']->getAmount()->asString(), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'grandtotal taxentry 2 percent');
        $this->assertSame('24.0000', $taxEntries['2-15']->getAmount()->asString(), 'grandtotal taxentry 2 amount');
        $this->assertEquals(20, $taxEntries['shipping-20']->getPercent(), 'grandtotal taxentry 3 percent');
        $this->assertSame('1.6667', $taxEntries['shipping-20']->getAmount()->asString(), 'grandtotal taxentry 3 amount');
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

        $this->assertSame('250.0000', $subTotal->getGrossAmount()->asString(), 'subtotal gross');
        $this->assertSame('203.5572', $subTotal->getNetAmount()->asString(), 'subtotal net');
        $taxEntries = $subTotal->getTaxEntries();
        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'subtotal taxentry 1 percent');
        $this->assertSame('20.3558', $taxEntries['1-10']->getAmount()->asString(), 'subtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'subtotal taxentry 2 percent');
        $this->assertSame('26.0870', $taxEntries['2-15']->getAmount()->asString(), 'subtotal taxentry 2 amount');

        $this->assertSame('260.0000', $grandTotal->getGrossAmount()->asString(), 'grandtotal gross');
        $this->assertSame('211.8905', $grandTotal->getNetAmount()->asString(), 'grandtotal net');
        $taxEntries = $grandTotal->getTaxEntries();

        $this->assertEquals(10, $taxEntries['1-10']->getPercent(), 'grandtotal taxentry 1 percent');
        $this->assertSame('20.3558', $taxEntries['1-10']->getAmount()->asString(), 'grandtotal taxentry 1 amount');
        $this->assertEquals(15, $taxEntries['2-15']->getPercent(), 'grandtotal taxentry 2 percent');
        $this->assertSame('26.0870', $taxEntries['2-15']->getAmount()->asString(), 'grandtotal taxentry 2 amount');

        $this->assertEquals(20, $taxEntries['shipping-20']->getPercent(), 'grandtotal taxentry 3 percent');
        $this->assertSame('1.6667', $taxEntries['shipping-20']->getAmount()->asString(), 'grandtotal taxentry 3 amount');
    }
}
