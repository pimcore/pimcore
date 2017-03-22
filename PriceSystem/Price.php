<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem;

use OnlineShop\Framework\PriceSystem\TaxManagement\TaxCalculationService;
use OnlineShop\Framework\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;

class Price implements IPrice {

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var float
     */
    private $grossAmount;

    /**
     * @var float
     */
    private $netAmount;

    /**
     * @var string
     */
    private $taxEntryCombinationMode = TaxEntry::CALCULATION_MODE_COMBINE;

    /**
     * @var bool
     */
    private $minPrice;

    /**
     * @var TaxEntry[]
     */
    private $taxEntries = [];


    /**
     * Price constructor.
     * @param $amount
     * @param Currency $currency
     * @param bool $minPrice
     */
    function __construct($amount, Currency $currency, $minPrice = false) {
        $this->grossAmount = $amount;
        $this->currency = $currency;
        $this->minPrice = $minPrice;
    }
    function __toString(){
        return $this->getCurrency()->toCurrency($this->grossAmount);
    }


    /**
     * @return bool
     */
    public function isMinPrice() {
        return $this->minPrice;
    }

    /**
     * sets amount of price, depending on $priceMode and $recalc it sets net price or gross price and recalculates the
     * corresponding net or gross price.
     *
     * @param float $amount
     * @param string $priceMode - default to PRICE_MODE_GROSS
     * @param bool $recalc - default to false
     */
    public function setAmount($amount, $priceMode = self::PRICE_MODE_GROSS, $recalc = false) {
        switch ($priceMode) {
            case self::PRICE_MODE_GROSS:
                $this->grossAmount = $amount;
                break;
            case self::PRICE_MODE_NET:
                $this->netAmount = $amount;
                break;
        }

        if($recalc) {
            $this->updateTaxes($priceMode);
        }

    }

    /**
     * Returns $grossAmount
     *
     * @return float
     */
    function getAmount() {
        return $this->grossAmount;
    }

    /**
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency) {
        $this->currency = $currency;
    }

    /**
     * @return Currency
     */
    function getCurrency() {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getGrossAmount()
    {
        return $this->grossAmount;
    }

    /**
     * @return float
     */
    public function getNetAmount()
    {
        return $this->netAmount;
    }

    /**
     * @return TaxEntry[]
     */
    public function getTaxEntries()
    {
        return $this->taxEntries;
    }

    /**
     * @return string
     */
    public function getTaxEntryCombinationMode()
    {
        return $this->taxEntryCombinationMode;
    }

    /**
     * @param float $grossAmount
     * @return void
     */
    public function setGrossAmount($grossAmount, $recalc = false)
    {
        $this->grossAmount = $grossAmount;

        if($recalc) {
            $this->updateTaxes(TaxCalculationService::CALCULATION_FROM_GROSS);
        }
    }

    /**
     * @param float $netAmount
     * @return void
     */
    public function setNetAmount($netAmount, $recalc = false)
    {
        $this->netAmount = $netAmount;

        if($recalc) {
            $this->updateTaxes(TaxCalculationService::CALCULATION_FROM_NET);
        }
    }

    /**
     * @param TaxEntry[] $taxEntries
     * @return void
     */
    public function setTaxEntries($taxEntries)
    {
        $this->taxEntries = $taxEntries;
    }

    /**
     * @param string $taxEntryCombinationMode
     * @return void
     */
    public function setTaxEntryCombinationMode($taxEntryCombinationMode)
    {
        $this->taxEntryCombinationMode = $taxEntryCombinationMode;
    }

    /**
     * Calls calculation service and updates taxes
     *
     * @param $calculationMode
     */
    protected function updateTaxes($calculationMode) {
        $taxCalculationService = new TaxCalculationService();
        $taxCalculationService->updateTaxes($this, $calculationMode);
    }
}
