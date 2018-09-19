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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;

class TaxCalculationService
{
    const CALCULATION_FROM_NET = 'net';
    const CALCULATION_FROM_GROSS = 'gross';

    /**
     * Updates taxes in given price by using its tax entries and net or gross amount based on the given $calculationMode
     *
     * @param IPrice $price
     * @param string $calculationMode - valid options are TaxCalculationService::CALCULATION_FROM_NET (default) and TaxCalculationService::CALCULATION_FROM_GROSS
     *
     * @return IPrice
     *
     * @throws UnsupportedException
     */
    public function updateTaxes(IPrice $price, string $calculationMode = self::CALCULATION_FROM_NET)
    {
        switch ($calculationMode) {
            case self::CALCULATION_FROM_NET:
                return $this->calculationFromNet($price);
            case self::CALCULATION_FROM_GROSS:
                return $this->calculationFromGross($price);
            default:
                throw new UnsupportedException('Calculation Mode [' . $calculationMode . '] not supported.');
        }
    }

    /**
     * Calculates taxes based on the net amount of the price and the tax entries
     *
     * @param IPrice $price
     *
     * @return IPrice
     *
     * @throws UnsupportedException
     */
    protected function calculationFromNet(IPrice $price): IPrice
    {
        $netAmount = $price->getNetAmount();
        $grossAmount = $netAmount;

        $taxEntries = $price->getTaxEntries();
        if (empty($taxEntries)) {
            $price->setGrossAmount($grossAmount);

            return $price;
        }

        switch ($price->getTaxEntryCombinationMode()) {
            case TaxEntry::CALCULATION_MODE_COMBINE:
                foreach ($taxEntries as $entry) {
                    $amount = $netAmount->mul($entry->getPercent() / 100);
                    $entry->setAmount($amount);

                    $grossAmount = $grossAmount->add($amount);
                }

                break;

            case TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER:
                foreach ($taxEntries as $entry) {
                    $amount = $grossAmount->mul($entry->getPercent() / 100);
                    $entry->setAmount($amount);

                    $grossAmount = $grossAmount->add($amount);
                }

                break;

            default:
                throw new UnsupportedException('Combination Mode [' . $price->getTaxEntryCombinationMode() . '] cannot be recalculated.');
                break;
        }

        $price->setGrossAmount($grossAmount);

        return $price;
    }

    /**
     * Calculates taxes based on the gross amount of the price and the tax entries
     *
     * @param IPrice $price
     *
     * @return IPrice
     *
     * @throws UnsupportedException
     */
    protected function calculationFromGross(IPrice $price): IPrice
    {
        $grossAmount = $price->getGrossAmount();
        $netAmount = $grossAmount;

        $taxEntries = $price->getTaxEntries();
        if (empty($taxEntries)) {
            $price->setNetAmount($netAmount);

            return $price;
        }

        /** @var TaxEntry[] $reverseTaxEntries */
        $reverseTaxEntries = array_reverse($taxEntries);

        switch ($price->getTaxEntryCombinationMode()) {
            case TaxEntry::CALCULATION_MODE_COMBINE:
                $totalTaxAmount = 100;

                foreach ($taxEntries as $entry) {
                    $totalTaxAmount += $entry->getPercent();
                }

                foreach ($reverseTaxEntries as $entry) {
                    $amount = $grossAmount->mul($entry->getPercent())->div($totalTaxAmount);
                    $entry->setAmount($amount);
                }

                $netAmount = $grossAmount->mul(100)->div($totalTaxAmount);

                break;

            case TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER:
                $currentGrossAmount = $grossAmount;

                foreach ($reverseTaxEntries as $entry) {
                    $amount = $currentGrossAmount->mul($entry->getPercent())->div(100 + $entry->getPercent());
                    $entry->setAmount($amount);

                    $currentGrossAmount = $currentGrossAmount->sub($amount);
                }

                $netAmount = $currentGrossAmount;

                break;

            default:
                throw new UnsupportedException('Combination Mode [' . $price->getTaxEntryCombinationMode() . '] cannot be recalculated.');
                break;

        }

        $price->setNetAmount($netAmount);

        return $price;
    }
}
