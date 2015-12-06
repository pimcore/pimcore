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

namespace OnlineShop\Framework\PriceSystem;

/**
 * Class AttributePriceInfo
 *
 * attribute info for attribute price system
 */
class AttributePriceInfo extends AbstractPriceInfo implements IPriceInfo {

    /**
     * @var IPrice
     */
    protected $price;

    /**
     * @var IPrice
     */
    protected $totalPrice;


    public function __construct(IPrice $price, $quantity, IPrice $totalPrice) {
        $this->price = $price;
        $this->totalPrice = $totalPrice;
        $this->quantity = $quantity;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getTotalPrice() {
        return $this->totalPrice;
    }

    /**
     * try to delegate all other functions to the product
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        return $this->product->$name($arguments);
    }

}
