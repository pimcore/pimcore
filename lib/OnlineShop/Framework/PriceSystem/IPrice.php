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


namespace OnlineShop\Framework\PriceSystem;
use OnlineShop\Framework\PriceSystem\TaxManagement\TaxEntry;

/**
 * Interface for price implementations of online shop framework
 */
interface IPrice {

    const PRICE_MODE_NET = "net";
    const PRICE_MODE_GROSS = "gross";

    /**
     * @abstract
     * @return float
     */
    public function getAmount();

    /**
     * @abstract
     * @return \Zend_Currency
     */
    public function getCurrency();

    /**
     * @abstract
     * @return bool
     */
    public function isMinPrice();

    /**
     * @param float $amount
     * @param string $priceMode
     * @param bool $recalc
     * @return mixed
     */
    public function setAmount($amount, $priceMode = self::PRICE_MODE_GROSS, $recalc = false);

    /**
     * @return float
     */
    public function getGrossAmount();

    /**
     * @return float
     */
    public function getNetAmount();

    /**
     * @return TaxEntry[]
     */
    public function getTaxEntries();

    /**
     * @return string
     */
    public function getTaxEntryCombinationMode();

    /**
     * @param float $grossAmount
     * @param bool $recalc
     * @return void
     */
    public function setGrossAmount($grossAmount, $recalc = false);

    /**
     * @param float $netAmount
     * @param bool $recalc
     * @return void
     */
    public function setNetAmount($netAmount, $recalc = false);

    /**
     * @param array $taxEntries
     * @return void
     */
    public function setTaxEntries($taxEntries);

    /**
     * @param string $taxEntryCombinationMode
     * @return void
     */
    public function setTaxEntryCombinationMode($taxEntryCombinationMode);

}
 
