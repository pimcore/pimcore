<?php
namespace EcommerceFramework;


use Codeception\Util\Stub;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Model\Object\OnlineShopTaxClass;

class TaxCalculationTest extends \Codeception\Test\Unit
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
    public function testTaxCalculationService()
    {
        $taxCalculationService = new TaxCalculationService();

        $price = new Price(100, new Currency("EUR"));
        $price->setNetAmount(90);
        $taxCalculationService->updateTaxes($price);

        $this->assertEquals(90, $price->getGrossAmount(), "No tax entries > net and gross should be equal");


        //single tax entry 10%
        $taxEntries = [
            new TaxEntry(10, 0)
        ];
        $price->setTaxEntries($taxEntries);
        $taxCalculationService->updateTaxes($price);
        $this->assertEquals(99, $price->getGrossAmount(), "Tax 10%, calc from net price");

        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(9, $taxEntries[0]->getAmount(), "Tax 10%, tax entry amount");

        $price->setGrossAmount(100);
        $taxCalculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_GROSS);
        $this->assertEquals(90.91, round($price->getNetAmount(), 2), "Tax 10%, calc from gross price");


        //single tax entry 15%
        $taxEntries = [
            new TaxEntry(15, 0)
        ];
        $price->setTaxEntries($taxEntries);
        $price->setGrossAmount(110, true);
        $this->assertEquals(95.65, round($price->getNetAmount(), 2), "Tax 15%, calc from gross price with automatic recalc");
        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(14.35, round($taxEntries[0]->getAmount(), 2), "Tax 15%, tax entry amount");


        $price->setNetAmount(100, true);
        $this->assertEquals(115, round($price->getGrossAmount(), 2), "Tax 15%, calc from net price with automatic recalc");
        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(15, round($taxEntries[0]->getAmount(), 2), "Tax 15%, tax entry amount");



        //multiple tax entry 12% 4% one-after-another
        $taxEntries = [
            new TaxEntry(12, 0),
            new TaxEntry(4, 0),
        ];
        $price->setNetAmount(90);
        $price->setTaxEntries($taxEntries);
        $price->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);
        $taxCalculationService->updateTaxes($price);
        $this->assertEquals(104.83, round($price->getGrossAmount(), 2), "Tax 12% + 4% one-after-another, calc from net price");

        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(10.8, round($taxEntries[0]->getAmount(), 2), "Tax 12% + 4% one-after-another, tax entry 1 amount");
        $this->assertEquals(4.03, round($taxEntries[1]->getAmount(), 2), "Tax 12% + 4% one-after-another, tax entry 2 amount");

        $price->setGrossAmount(100);
        $taxCalculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_GROSS);
        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(85.85, round($price->getNetAmount(), 2), "Tax 12% + 4% one-after-another, calc from gross price");
        $this->assertEquals(10.30, round($taxEntries[0]->getAmount(), 2), "Tax 12% + 4% one-after-another, tax entry 1 amount");
        $this->assertEquals(3.85, round($taxEntries[1]->getAmount(), 2), "Tax 12% + 4% one-after-another, tax entry 2 amount");


        //multiple tax entry 12% 4% combine
        $taxEntries = [
            new TaxEntry(12, 0),
            new TaxEntry(4, 0),
        ];
        $price->setNetAmount(90);
        $price->setTaxEntries($taxEntries);
        $price->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_COMBINE);
        $taxCalculationService->updateTaxes($price);
        $this->assertEquals(104.4, round($price->getGrossAmount(), 2), "Tax 12% + 4% combine, calc from net price");

        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(10.8, round($taxEntries[0]->getAmount(), 2), "Tax 12% + 4% combine, tax entry 1 amount");
        $this->assertEquals(3.6, round($taxEntries[1]->getAmount(), 2), "Tax 12% + 4% combine, tax entry 2 amount");

        $price->setGrossAmount(100);
        $taxCalculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_GROSS);
        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(86.21, round($price->getNetAmount(), 2), "Tax 12% + 4% combine, calc from gross price");
        $this->assertEquals(10.34, round($taxEntries[0]->getAmount(), 2), "Tax 12% + 4% combine, tax entry 1 amount");
        $this->assertEquals(3.45, round($taxEntries[1]->getAmount(), 2), "Tax 12% + 4% combine, tax entry 2 amount");

    }



    public function testPriceSystem() {

        $config = new \stdClass();

        $priceSystem = Stub::construct(AttributePriceSystem::class, [$config], [
            "getTaxClassForProduct" => function() {
                $taxClass = new OnlineShopTaxClass();
                $taxClass->setTaxEntryCombinationType(TaxEntry::CALCULATION_MODE_COMBINE);
                return $taxClass;
            },
            "getPriceClassInstance" => function($amount) {
                return new Price($amount, new Currency("EUR"));
            },
            "calculateAmount" => function() {
                return 100;
            }
        ]);

        $product = Stub::construct(AbstractProduct::class, [], [
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

        /**
         * @var $product AbstractProduct
         */
        $this->assertEquals(100, round($product->getOSPrice()->getAmount(), 2), "Get Price Amount without any tax entries");

    }
}