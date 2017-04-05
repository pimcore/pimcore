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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;

/**
 * Interface for price implementations of online shop framework
 */
interface IPrice
{
    const PRICE_MODE_NET = "net";
    const PRICE_MODE_GROSS = "gross";

    /**
     * Returns $grossAmount
     *
     * @abstract
     * @return float
     */
    public function getAmount();

    /**
     * @abstract
     * @return Currency
     */
    public function getCurrency();

    /**
     * @abstract
     * @return bool
     */
    public function isMinPrice();

    /**
     * sets amount of price, depending on $priceMode and $recalc it sets net price or gross price and recalculates the
     * corresponding net or gross price.
     *
     * @param float $amount
     * @param string $priceMode - default to PRICE_MODE_GROSS
     * @param bool $recalc - default to false
     */
    public function setAmount($amount, $priceMode = self::PRICE_MODE_GROSS, $recalc = false);

    /**
     * Returns gross amount of price
     *
     * @return float
     */
    public function getGrossAmount();

    /**
     * Returns net amount of price
     *
     * @return float
     */
    public function getNetAmount();

    /**
     * Returns tax entries of price as an array
     *
     * @return TaxEntry[]
     */
    public function getTaxEntries();

    /**
     * Returns tax entry combination mode needed for tax calculation
     *
     * @return string
     */
    public function getTaxEntryCombinationMode();

    /**
     * Sets gross amount of price. If $recalc is set to true, corresponding net price
     * is calculated based on tax entries and tax entry combination mode.
     *
     * @param float $grossAmount
     * @param bool $recalc
     * @return void
     */
    public function setGrossAmount($grossAmount, $recalc = false);

    /**
     * Sets net amount of price. If $recalc is set to true, corresponding gross price
     * is calculated based on tax entries and tax entry combination mode.
     *
     * @param float $netAmount
     * @param bool $recalc
     * @return void
     */
    public function setNetAmount($netAmount, $recalc = false);

    /**
     * Sets tax entries for price.
     *
     * @param array $taxEntries
     * @return void
     */
    public function setTaxEntries($taxEntries);

    /**
     * Sets $taxEntryCombinationMode for price.
     *
     * @param string $taxEntryCombinationMode
     * @return void
     */
    public function setTaxEntryCombinationMode($taxEntryCombinationMode);
}
