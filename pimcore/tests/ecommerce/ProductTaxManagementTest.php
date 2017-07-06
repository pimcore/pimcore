<?php

namespace Pimcore\Tests\Ecommerce;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;
use Pimcore\Model\Object\OnlineShopTaxClass;
use Pimcore\Tests\Test\TestCase;

class ProductTaxManagementTest extends TestCase
{
    /**
     * @var \EcommerceFramework\UnitTester
     */
    protected $tester;

    private function setUpProduct($grossPrice, $taxes = [], $combinationType = TaxEntry::CALCULATION_MODE_COMBINE)
    {
        $grossPrice = PriceAmount::create($grossPrice);

        $taxClass = new OnlineShopTaxClass();
        $taxEntries = new \Pimcore\Model\Object\Fieldcollection();

        foreach ($taxes as $name => $tax) {
            $entry = new \Pimcore\Model\Object\Fieldcollection\Data\TaxEntry();
            $entry->setPercent($tax);
            $entry->setName($name);
            $taxEntries->add($entry);
        }
        $taxClass->setTaxEntries($taxEntries);
        $taxClass->setTaxEntryCombinationType($combinationType);

        $config = new \stdClass();

        $priceSystem = Stub::construct(AttributePriceSystem::class, [$config], [
            'getTaxClassForProduct' => function () use ($taxClass) {
                return $taxClass;
            },
            'getPriceClassInstance' => function (PriceAmount $amount) {
                return new Price($amount, new Currency('EUR'));
            },
            'calculateAmount' => function () use ($grossPrice): PriceAmount {
                return $grossPrice;
            }
        ]);

        return Stub::construct(AbstractProduct::class, [], [
            'getId' => function () {
                return 5;
            },
            'getPriceSystemImplementation' => function () use ($priceSystem) {
                return $priceSystem;
            },
            'getCategories' => function () {
                return [];
            }
        ]);
    }

    // tests
    public function testPriceWithoutTaxEntries()
    {
        $product = $this->setUpProduct(100);

        /**
         * @var $product AbstractProduct
         */
        $this->assertEquals(100, $product->getOSPrice()->getAmount()->asNumeric(), 'Get Price Amount without any tax entries');
        $this->assertEquals(100, $product->getOSPrice()->getNetAmount()->asNumeric(), 'Get net amount without any tax entries');
        $this->assertEquals(100, $product->getOSPrice()->getGrossAmount()->asNumeric(), 'Get gross amount without any tax entries');
    }

    public function testPriceWithTaxEntriesCombine()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_COMBINE);

        /**
         * @var $product AbstractProduct
         */
        $price = $product->getOSPrice();
        $this->assertEquals(100, $price->getGrossAmount(), 'Get gross amount with tax 10% + 15% combine');
        $this->assertEquals(80, $price->getNetAmount(), 'Get net amount 10% + 15% combine');
    }

    public function testPriceWithTaxEntriesOneAfterAnother()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        /**
         * @var $product AbstractProduct
         */
        $price = $product->getOSPrice();
        $this->assertEquals(100, $price->getGrossAmount(), 'Get gross amount with tax 10% + 15% one-after-another');
        $this->assertEquals(79.05, $price->getNetAmount(), 'Get net amount 10% + 15% one-after-another');
    }
}
