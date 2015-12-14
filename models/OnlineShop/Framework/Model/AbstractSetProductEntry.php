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

namespace OnlineShop\Framework\Model;

/**
 * Class for product entry of a set product - container for product and quantity
 */
class AbstractSetProductEntry {

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var \OnlineShop\Framework\Model\ICheckoutable
     */
    private $product;

    public function __construct(\OnlineShop\Framework\Model\ICheckoutable $product, $quantity = 1) {
        $this->product = $product;
        $this->quantity = $quantity;
    }


    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @return void
     */
    public function setProduct(\OnlineShop\Framework\Model\ICheckoutable $product) {
        $this->product = $product;
    }

    /**
     * @return \OnlineShop\Framework\Model\ICheckoutable
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param  int $quantity
     * @return void
     */
    public function setQuantity($quantity) {
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * returns id of set product
     *
     * @return int
     */
    public function getId() {
        if($this->getProduct()) {
            return $this->getProduct()->getId();
        }
        return null;
    }
}
