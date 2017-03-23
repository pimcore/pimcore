<?php
namespace EcommerceFramework;


use Codeception\Util\Stub;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Model\Object\OnlineShopTaxClass;

class ProductTaxManagementTest extends \Codeception\Test\Unit
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

    private function setUpProduct($grossPrice, $taxes = [], $combinationType = TaxEntry::CALCULATION_MODE_COMBINE) {

        $taxClass = new OnlineShopTaxClass();
        $taxEntries = new \Pimcore\Model\Object\Fieldcollection();

        foreach($taxes as $name => $tax) {
            $entry = new \Pimcore\Model\Object\Fieldcollection\Data\TaxEntry();
            $entry->setPercent($tax);
            $entry->setName($name);
            $taxEntries->add($entry);
        }
        $taxClass->setTaxEntries($taxEntries);
        $taxClass->setTaxEntryCombinationType($combinationType);


        $config = new \stdClass();

        $priceSystem = Stub::construct(AttributePriceSystem::class, [$config], [
            "getTaxClassForProduct" => function() use ($taxClass) {
                return $taxClass;
            },
            "getPriceClassInstance" => function($amount) {
                return new Price($amount, new Currency("EUR"));
            },
            "calculateAmount" => function() use ($grossPrice) {
                return $grossPrice;
            }
        ]);

        return Stub::construct(AbstractProduct::class, [], [
            "getId" => function() {
                return 5;
            },
            "getPriceSystemImplementation" => function() use ($priceSystem) {
                return $priceSystem;
            },
            "getCategories" => function() {
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
        $this->assertEquals(100, round($product->getOSPrice()->getAmount(), 2), "Get Price Amount without any tax entries");
        $this->assertEquals(100, round($product->getOSPrice()->getNetAmount(), 2), "Get net amount without any tax entries");
        $this->assertEquals(100, round($product->getOSPrice()->getGrossAmount(), 2), "Get gross amount without any tax entries");

    }

    public function testPriceWithTaxEntriesCombine() {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_COMBINE);

        /**
         * @var $product AbstractProduct
         */

        $price = $product->getOSPrice();
        $this->assertEquals(100, round($price->getGrossAmount(), 2), "Get gross amount with tax 10% + 15% combine");
        $this->assertEquals(80, round($price->getNetAmount(), 2), "Get net amount 10% + 15% combine");

    }


    public function testPriceWithTaxEntriesOneAfterAnother() {
        $product = $this->setUpProduct(100, [1 => 10, 2 => 15], TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        /**
         * @var $product AbstractProduct
         */

        $price = $product->getOSPrice();
        $this->assertEquals(100, round($price->getGrossAmount(), 2), "Get gross amount with tax 10% + 15% one-after-another");
        $this->assertEquals(79.05, round($price->getNetAmount(), 2), "Get net amount 10% + 15% one-after-another");

    }
}