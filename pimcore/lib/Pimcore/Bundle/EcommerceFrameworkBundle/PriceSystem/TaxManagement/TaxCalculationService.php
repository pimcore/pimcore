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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;

/**
 * Class TaxCalculationService
 */
class TaxCalculationService
{
    const CALCULATION_FROM_NET = "net";
    const CALCULATION_FROM_GROSS = "gross";

    /**
     * Updates taxes in given price by using its tax entries and net or gross amount based on the given $calculationMode
     *
     *
     * @param IPrice $price
     * @param string $calculationMode - valid options are TaxCalculationService::CALCULATION_FROM_NET (default) and TaxCalculationService::CALCULATION_FROM_GROSS
     * @return IPrice
     * @throws UnsupportedException
     */
    public function updateTaxes(IPrice $price, $calculationMode = self::CALCULATION_FROM_NET)
    {
        switch ($calculationMode) {
            case self::CALCULATION_FROM_NET:
                return $this->calculationFromNet($price);
            case self::CALCULATION_FROM_GROSS:
                return $this->calculationFromGross($price);
            default:
                throw new UnsupportedException("Calculation Mode [" . $calculationMode . "] not supported.");
        }
    }

    /**
     * calculates taxes based on the net amount of the price and the tax entries
     *
     * @param IPrice $price
     * @return IPrice
     * @throws UnsupportedException
     */
    protected function calculationFromNet(IPrice $price)
    {
        switch ($price->getTaxEntryCombinationMode()) {
            case TaxEntry::CALCULATION_MODE_COMBINE:

                $taxEntries = $price->getTaxEntries();
                $netAmount = $price->getNetAmount();
                $grossAmount = $netAmount;

                if ($taxEntries) {
                    foreach ($taxEntries as $entry) {
                        $amount = $netAmount * $entry->getPercent() / 100;
                        $entry->setAmount($amount);
                        $grossAmount += $amount;
                    }

                    $price->setGrossAmount($grossAmount);
                } else {
                    $price->setGrossAmount($netAmount);
                }



                break;

            case TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER:

                $taxEntries = $price->getTaxEntries();
                $netAmount = $price->getNetAmount();
                $grossAmount = $netAmount;

                if ($taxEntries) {
                    foreach ($taxEntries as $entry) {
                        $amount = $grossAmount * $entry->getPercent() / 100;
                        $entry->setAmount($amount);
                        $grossAmount += $amount;
                    }
                    $price->setGrossAmount($grossAmount);
                } else {
                    $price->setGrossAmount($netAmount);
                }



                break;

            default:
                throw new UnsupportedException("Combination Mode [" . $price->getTaxEntryCombinationMode() . "] cannot be recalculated.");
                break;

        }

        return $price;
    }


    /**
     * Calculates taxes based on the gross amount of the price and the tax entries
     *
     * @param IPrice $price
     * @return IPrice
     * @throws UnsupportedException
     */
    protected function calculationFromGross(IPrice $price)
    {
        switch ($price->getTaxEntryCombinationMode()) {

            case TaxEntry::CALCULATION_MODE_COMBINE:

                $taxEntries = $price->getTaxEntries();

                if ($taxEntries) {
                    $reverseTaxEntries = array_reverse($taxEntries);

                    $totalTaxAmount = 100;
                    foreach ($taxEntries as $entry) {
                        $totalTaxAmount += $entry->getPercent();
                    }

                    $grossAmount = $price->getGrossAmount();

                    foreach ($reverseTaxEntries as $entry) {
                        $amount = $grossAmount / $totalTaxAmount * $entry->getPercent();
                        $entry->setAmount($amount);
                    }

                    $price->setNetAmount($grossAmount / $totalTaxAmount * 100);
                } else {
                    $price->setNetAmount($price->getGrossAmount());
                }

                break;

            case TaxEntry::CALCULATION_MODE_ONE_AFTER_ANOTHER:

                $taxEntries = $price->getTaxEntries();

                if ($taxEntries) {
                    $reverseTaxEntries = array_reverse($taxEntries);

                    $grossAmount = $price->getGrossAmount();
                    $currentGrossAmount = $grossAmount;

                    foreach ($reverseTaxEntries as $entry) {
                        $amount = $currentGrossAmount / (100 + $entry->getPercent()) * $entry->getPercent();
                        $entry->setAmount($amount);
                        $currentGrossAmount = $currentGrossAmount - $amount;
                    }

                    $price->setNetAmount($currentGrossAmount);
                } else {
                    $price->setNetAmount($price->getGrossAmount());
                }

                break;

            default:
                throw new UnsupportedException("Combination Mode [" . $price->getTaxEntryCombinationMode() . "] cannot be recalculated.");
                break;

        }

        return $price;
    }
}
