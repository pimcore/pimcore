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

class Price implements IPrice {
    private $currency;
    private $amount;
    private $minPrice;

    /** @return \Zend_Currency*/
    function getCurrency() {
        return $this->currency;
    }

    /** @return double*/
    function getAmount() {
        return $this->amount;
    }


    function __construct($amount, \Zend_Currency $currency, $minPrice = false) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->minPrice = $minPrice;
    }
    function __toString(){
        return $this->getCurrency()->toCurrency($this->amount);
    }


    /**
     * @return bool
     */
    public function isMinPrice() {
        return $this->minPrice;
    }

    /**
     * @param float $amount
     * @return void
     */
    public function setAmount($amount) {
        $this->amount = $amount;
    }
}
