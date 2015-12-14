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
 * Abstract base class price info
 */
 class AbstractPriceInfo implements IPriceInfo {

     /**
      * @static
      * @return AbstractPriceInfo
      */
    public static function getInstance(){
        return new static(func_get_args());
    }


     /**
     * @var \OnlineShop\Framework\PriceSystem\IPriceSystem
     */
    private $priceSystem;


     /** @var int */
     protected $quantity;

     /**
      * @var \OnlineShop\Framework\Model\ICheckoutable
      */
     protected $product;

     /**
      * @var \OnlineShop\Framework\Model\ICheckoutable[]
      */
     protected $products;

    /**
     * @param int|string $quantity
     * numeric quantity or constant IPriceInfo::MIN_PRICE
     */
    public function setQuantity($quantity) {
        $this->quantity = $quantity;
    }

    /**
     * @return int|string
     */
    public function getQuantity() {
        return $this->quantity;
    }



    /**
     * @return bool
     */
    public function isMinPrice(){
        return $this->getQuantity()===self::MIN_PRICE;
    }

    /**
     * @param IPriceSystem $priceSystem
     */
    public function setPriceSystem($priceSystem) {
        $this->priceSystem = $priceSystem;
    }

    /**
     * @return IPriceSystem
     */
    protected  function getPriceSystem() {
        return $this->priceSystem;
    }

     /**
      * @throws \OnlineShop\Framework\Exception\UnsupportedException
      * @return IPrice
      */
     public function getPrice() {
         throw new \OnlineShop\Framework\Exception\UnsupportedException(__METHOD__ . " is not supported for " . get_class($this));
     }

     /**
      * @throws \OnlineShop\Framework\Exception\UnsupportedException
      * @return IPrice
      */
     public function getTotalPrice() {
         throw new \OnlineShop\Framework\Exception\UnsupportedException(__METHOD__ . " is not supported for " . get_class($this));
     }

     public function setProduct(\OnlineShop\Framework\Model\ICheckoutable $product) {
         $this->product = $product;
     }

     public function getProduct() {
         return $this->product;
     }

     public function setProducts($products) {
         $this->products = $products;
     }

     public function getProducts() {
         return $this->products;
     }
}