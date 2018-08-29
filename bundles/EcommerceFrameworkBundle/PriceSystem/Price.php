<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxCalculationService;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class Price implements IPrice
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var Decimal
     */
    private $grossAmount;

    /**
     * @var Decimal
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
     * @param Decimal $amount
     * @param Currency $currency
     * @param bool $minPrice
     */
    public function __construct(Decimal $amount, Currency $currency, bool $minPrice = false)
    {
        $this->grossAmount = $this->netAmount = $amount;
        $this->currency = $currency;
        $this->minPrice = $minPrice;
    }

    public function __toString()
    {
        return $this->getCurrency()->toCurrency($this->grossAmount);
    }

    /**
     * @inheritdoc
     */
    public function isMinPrice(): bool
    {
        return $this->minPrice;
    }

    /**
     * @inheritdoc
     */
    public function setAmount(Decimal $amount, string $priceMode = self::PRICE_MODE_GROSS, bool $recalc = false)
    {
        switch ($priceMode) {
            case self::PRICE_MODE_GROSS:
                $this->setGrossAmount($amount, $recalc);
                break;
            case self::PRICE_MODE_NET:
                $this->setNetAmount($amount, $recalc);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Price mode "%s" is not supported', $priceMode));
        }
    }

    /**
     * @inheritdoc
     */
    public function getAmount(): Decimal
    {
        return $this->grossAmount;
    }

    /**
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * @inheritdoc
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @inheritdoc
     */
    public function getGrossAmount(): Decimal
    {
        return $this->grossAmount;
    }

    /**
     * @inheritdoc
     */
    public function getNetAmount(): Decimal
    {
        return $this->netAmount;
    }

    /**
     * @return TaxEntry[]
     */
    public function getTaxEntries(): array
    {
        return $this->taxEntries;
    }

    /**
     * @inheritdoc
     */
    public function getTaxEntryCombinationMode(): string
    {
        return $this->taxEntryCombinationMode;
    }

    /**
     * @inheritdoc
     */
    public function setGrossAmount(Decimal $grossAmount, bool $recalc = false)
    {
        $this->grossAmount = $grossAmount;

        if ($recalc) {
            $this->updateTaxes(TaxCalculationService::CALCULATION_FROM_GROSS);
        }
    }

    /**
     * @inheritdoc
     */
    public function setNetAmount(Decimal $netAmount, bool $recalc = false)
    {
        $this->netAmount = $netAmount;

        if ($recalc) {
            $this->updateTaxes(TaxCalculationService::CALCULATION_FROM_NET);
        }
    }

    /**
     * @inheritdoc
     */
    public function setTaxEntries(array $taxEntries)
    {
        $this->taxEntries = $taxEntries;
    }

    /**
     * @inheritdoc
     */
    public function setTaxEntryCombinationMode(string $taxEntryCombinationMode)
    {
        $this->taxEntryCombinationMode = $taxEntryCombinationMode;
    }

    /**
     * Calls calculation service and updates taxes
     *
     * @param string $calculationMode
     */
    protected function updateTaxes(string $calculationMode)
    {
        $taxCalculationService = new TaxCalculationService();
        $taxCalculationService->updateTaxes($this, $calculationMode);
    }
}
