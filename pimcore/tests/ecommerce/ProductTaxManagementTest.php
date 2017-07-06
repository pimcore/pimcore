<?php

namespace Pimcore\Tests\Ecommerce;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\Object\OnlineShopTaxClass;
use Pimcore\Tests\Test\TestCase;

class ProductTaxManagementTest extends TestCase
{
    /**
     * @param float $grossPrice
     * @param array $taxes
     * @param string $combinationType
     *
     * @return AbstractProduct|\PHPUnit_Framework_MockObject_Stub
     */
    private function setUpProduct($grossPrice, $taxes = [], $combinationType = TaxEntry::CALCULATION_MODE_COMBINE)
    {
        $grossPrice = Decimal::create($grossPrice);

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
            'getPriceClassInstance' => function (Decimal $amount) {
                return new Price($amount, new Currency('EUR'));
            },
            'calculateAmount' => function () use ($grossPrice): Decimal {
                return $grossPrice;
            }
        ]);

        /** @var AbstractProduct|\PHPUnit_Framework_MockObject_Stub $product */
        $product = Stub::construct(AbstractProduct::class, [], [
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

        return $product;
    }

    public function testPriceWithoutTaxEntries()
    {
        $product = $this->setUpProduct(100);
        $price   = $product->getOSPrice();

        $this->assertSame('100.00', $price->getAmount()->asString(2), 'Get Price Amount without any tax entries');
        $this->assertSame('100.00', $price->getNetAmount()->asString(2), 'Get net amount without any tax entries');
        $this->assertSame('100.00', $price->getGrossAmount()->asString(2), 'Get gross amount without any tax entries');
    }

    public function testPriceWithTaxEntriesCombine()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_COMBINE);
        $price   = $product->getOSPrice();

        $this->assertSame('100.00', $price->getGrossAmount()->asString(2), 'Get gross amount with tax 10% + 15% combine');
        $this->assertSame('80.00', $price->getNetAmount()->asString(2), 'Get net amount 10% + 15% combine');
    }

    public function testPriceWithTaxEntriesOneAfterAnother()
    {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);
        $price   = $product->getOSPrice();

        $this->assertSame('100.00', $price->getGrossAmount()->asString(2), 'Get gross amount with tax 10% + 15% one-after-another');
        $this->assertSame('79.05', $price->getNetAmount()->asString(2), 'Get net amount 10% + 15% one-after-another');
    }
}
