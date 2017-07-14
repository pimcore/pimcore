<?php

namespace Pimcore\Tests\Ecommerce\PriceSystem;

use Codeception\Util\Stub;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\AttributePriceSystem;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\Object\OnlineShopTaxClass;
use Pimcore\Tests\Test\TestCase;

class TaxCalculationTest extends TestCase
{
    /**
     * @var TaxCalculationService
     */
    private $calculationService;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->calculationService = new TaxCalculationService();
    }

    public function testNetAndGrossDefaultToTheSameValue()
    {
        $price = new Price(Decimal::create(100), new Currency('EUR'));

        $this->assertEquals(100, $price->getAmount()->asNumeric());
        $this->assertEquals(100, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(100, $price->getNetAmount()->asNumeric());
    }

    public function testNetAndGrossAmountAreDifferentValues()
    {
        $price = new Price(Decimal::create(100), new Currency('EUR'));

        $this->assertEquals(100, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(100, $price->getNetAmount()->asNumeric());

        $price->setNetAmount(Decimal::create(90));

        $this->assertEquals(100, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(90, $price->getNetAmount()->asNumeric());
    }

    public function testNetAndGrossAreTheSameWithoutTaxEntries()
    {
        $price = new Price(Decimal::create(100), new Currency('EUR'));

        $price->setNetAmount(Decimal::create(90), false);

        $this->assertEquals(100, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(90, $price->getNetAmount()->asNumeric());

        $this->calculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_NET);

        $this->assertEquals(90, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(90, $price->getNetAmount()->asNumeric());
        $this->assertTrue($price->getNetAmount()->equals($price->getGrossAmount()), 'No tax entries > net and gross should be equal');
    }

    public function testTaxesAreUpdatesWithRecalcParam()
    {
        $price = new Price(Decimal::create(100), new Currency('EUR'));

        $price->setNetAmount(Decimal::create(90), true);

        $this->assertEquals(90, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(90, $price->getNetAmount()->asNumeric());

        $price->setGrossAmount(Decimal::create(110), true);

        $this->assertEquals(110, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(110, $price->getNetAmount()->asNumeric());
    }

    public function testSetAmount()
    {
        $price = new Price(Decimal::create(100), new Currency('EUR'));
        $price->setAmount(Decimal::create(110), IPrice::PRICE_MODE_GROSS, false);

        $this->assertEquals(110, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(100, $price->getNetAmount()->asNumeric());

        $price->setAmount(Decimal::create(120), IPrice::PRICE_MODE_GROSS, true);

        $this->assertEquals(120, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(120, $price->getNetAmount()->asNumeric());

        $price->setAmount(Decimal::create(90), IPrice::PRICE_MODE_NET, false);

        $this->assertEquals(120, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(90, $price->getNetAmount()->asNumeric());

        $price->setAmount(Decimal::create(80), IPrice::PRICE_MODE_NET, true);

        $this->assertEquals(80, $price->getGrossAmount()->asNumeric());
        $this->assertEquals(80, $price->getNetAmount()->asNumeric());
    }

    public function testSingleTaxEntryFromNet()
    {
        $price = new Price(Decimal::create(90), new Currency('EUR'));

        $this->assertTrue($price->getNetAmount()->equals($price->getGrossAmount()), 'No tax entries > net and gross should be equal');

        // single tax entry 10%
        $price->setTaxEntries([
            new TaxEntry(10, Decimal::create(0))
        ]);

        $this->calculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_NET);
        $this->assertEquals(99, $price->getGrossAmount()->asNumeric(), 'Tax 10%, calc from net price');

        $taxEntries = $price->getTaxEntries();
        $this->assertCount(1, $taxEntries);
        $this->assertEquals(9, $taxEntries[0]->getAmount()->asNumeric(), 'Tax 10%, tax entry amount');

        $price->setGrossAmount(Decimal::create(100));
        $this->calculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_GROSS);
        $this->assertSame('90.9091', $price->getNetAmount()->asString(), 'Tax 10%, calc from gross price');

        $this->assertTrue($price->getGrossAmount()->equals($price->getNetAmount()->add($taxEntries[0]->getAmount())));
    }

    public function testSingleTaxEntryFromGross()
    {
        $price = new Price(Decimal::create(0), new Currency('EUR'));
        $price->setTaxEntries([
            new TaxEntry(15, Decimal::create(0))
        ]);
        $price->setGrossAmount(Decimal::create(110), true);

        $this->assertEquals(110, $price->getGrossAmount()->asNumeric());
        $this->assertSame('95.6522', $price->getNetAmount()->asString(), 'Tax 15%, calc from gross price with automatic recalc');

        $taxEntries = $price->getTaxEntries();
        $this->assertCount(1, $taxEntries);

        $taxEntry = $taxEntries[0];
        $this->assertSame('14.3478', $taxEntry->getAmount()->asString(), 'Tax 15%, tax entry amount');

        // test if taxes add up to gross amount
        $addedTaxNetAmount = $price->getNetAmount()->add($taxEntry->getAmount());

        $this->assertEquals(110, $addedTaxNetAmount->asNumeric());
        $this->assertTrue($price->getGrossAmount()->equals($addedTaxNetAmount));

        $price->setNetAmount(Decimal::create(100), true);
        $this->assertEquals(115, $price->getGrossAmount()->asNumeric(), 'Tax 15%, calc from net price with automatic recalc');

        $taxEntries = $price->getTaxEntries();
        $this->assertEquals(15, $taxEntries[0]->getAmount()->asNumeric(), 'Tax 15%, tax entry amount');
    }

    public function testMultipleTaxEntriesOneAfterAnother()
    {
        $price = new Price(Decimal::create(90), new Currency('EUR'));
        $price->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER);

        // multiple tax entry 12% 4% one-after-another
        $price->setTaxEntries([
            new TaxEntry(12, Decimal::create(0)),
            new TaxEntry(4, Decimal::create(0)),
        ]);

        $this->assertEquals(90, $price->getGrossAmount()->asNumeric());
        $this->assertTrue($price->getGrossAmount()->equals($price->getNetAmount()));

        $this->calculationService->updateTaxes($price);

        $this->assertSame('104.8320', $price->getGrossAmount()->asString(), 'Tax 12% + 4% one-after-another, calc from net price');

        $taxEntries = $price->getTaxEntries();

        $this->assertCount(2, $taxEntries);
        $this->assertSame('10.8000', $taxEntries[0]->getAmount()->asString(), 'Tax 12% + 4% one-after-another, tax entry 1 amount');
        $this->assertSame('4.0320', $taxEntries[1]->getAmount()->asString(), 'Tax 12% + 4% one-after-another, tax entry 2 amount');

        $this->assertTaxesAddUp($price);
    }

    public function testMultipleTaxEntriesCombined()
    {
        $price = new Price(Decimal::create(90), new Currency('EUR'));
        $price->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_COMBINE);

        // multiple tax entry 12% 4% combine
        $price->setTaxEntries([
            new TaxEntry(12, Decimal::create(0)),
            new TaxEntry(4, Decimal::create(0)),
        ]);

        $this->calculationService->updateTaxes($price);

        $this->assertSame('104.4000', $price->getGrossAmount()->asString(), 'Tax 12% + 4% combine, calc from net price');

        $taxEntries = $price->getTaxEntries();

        $this->assertCount(2, $taxEntries);
        $this->assertSame('10.8000', $taxEntries[0]->getAmount()->asString(), 'Tax 12% + 4% combine, tax entry 1 amount');
        $this->assertSame('3.6000', $taxEntries[1]->getAmount()->asString(), 'Tax 12% + 4% combine, tax entry 2 amount');

        $this->assertTaxesAddUp($price);
    }

    public function testMultipleTaxEntriesCombinedFromGross()
    {
        $price = new Price(Decimal::create(100), new Currency('EUR'));
        $price->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_COMBINE);

        // multiple tax entry 12% 4% combine
        $price->setTaxEntries([
            new TaxEntry(12, Decimal::create(0)),
            new TaxEntry(4, Decimal::create(0)),
        ]);

        $this->calculationService->updateTaxes($price, TaxCalculationService::CALCULATION_FROM_GROSS);

        $taxEntries = $price->getTaxEntries();

        $this->assertCount(2, $taxEntries);
        $this->assertEquals('86.2069', $price->getNetAmount()->asString(), 'Tax 12% + 4% combine, calc from gross price');
        $this->assertEquals('10.3448', $taxEntries[0]->getAmount()->asString(), 'Tax 12% + 4% combine, tax entry 1 amount');
        $this->assertEquals('3.4483', $taxEntries[1]->getAmount()->asString(), 'Tax 12% + 4% combine, tax entry 2 amount');

        $this->assertTaxesAddUp($price);
    }

    private function assertTaxesAddUp(Price $price)
    {
        $calculatedGrossAmount = $price->getNetAmount();
        foreach ($price->getTaxEntries() as $taxEntry) {
            $calculatedGrossAmount = $calculatedGrossAmount->add($taxEntry->getAmount());
        }

        $this->assertEquals($price->getGrossAmount()->asString(), $calculatedGrossAmount->asString());
        $this->assertTrue($price->getGrossAmount()->equals($calculatedGrossAmount));
    }

    public function testPriceSystem()
    {
        $config = new \stdClass();

        $priceSystem = Stub::construct(AttributePriceSystem::class, [$config], [
            'getTaxClassForProduct' => function () {
                $taxClass = new OnlineShopTaxClass();
                $taxClass->setTaxEntryCombinationType(TaxEntry::CALCULATION_MODE_COMBINE);

                return $taxClass;
            },
            'getPriceClassInstance' => function ($amount) {
                return new Price(Decimal::create($amount), new Currency('EUR'));
            },
            'calculateAmount' => function () {
                return Decimal::create(100);
            }
        ]);

        /**
         * @var $product AbstractProduct
         */
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

        $this->assertEquals(
            100,
            $product->getOSPrice()->getAmount()->asNumeric(),
            'Get Price Amount without any tax entries'
        );
    }
}
