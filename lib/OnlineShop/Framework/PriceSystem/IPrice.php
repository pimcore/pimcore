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

/**
 * Interface for price implementations of online shop framework
 */
interface IPrice {

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
     * @abstract
     * @param float $amount
     * @return void
     */
    public function setAmount($amount);

}
 
