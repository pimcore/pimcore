<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 08.06.11
 * Time: 11:34
 * To change this template use File | Settings | File Templates.
 */
 
class OnlineShop_Framework_Impl_Price implements OnlineShop_Framework_IPrice {
    private $currency;
    private $amount;
    private $minPrice;

    /** @return Zend_Currency*/
    function getCurrency() {
        return $this->currency;
    }

    /** @return double*/
    function getAmount() {
        return $this->amount;
    }


    function __construct($amount, Zend_Currency $currency, $minPrice = false) {
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
