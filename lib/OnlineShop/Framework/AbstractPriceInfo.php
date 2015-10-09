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
 * Abstract base class price info
 */
 class OnlineShop_Framework_AbstractPriceInfo implements OnlineShop_Framework_IPriceInfo {

     /**
      * @static
      * @return OnlineShop_Framework_AbstractPriceInfo
      */
    public static function getInstance(){
        return new static(func_get_args());
    }


     /**
     * @var \OnlineShop_Framework_IPriceSystem
     */
    private $priceSystem;


     /** @var int */
     protected $quantity;

     /**
      * @var OnlineShop_Framework_ProductInterfaces_ICheckoutable
      */
     protected $product;

     /**
      * @var OnlineShop_Framework_ProductInterfaces_ICheckoutable[]
      */
     protected $products;

    /**
     * @param int|string $quantity
     * numeric quantity or constant OnlineShop_Framework_IPriceInfo::MIN_PRICE
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
     * @param \OnlineShop_Framework_IPriceSystem $priceSystem
     */
    public function setPriceSystem($priceSystem) {
        $this->priceSystem = $priceSystem;
    }

    /**
     * @return \OnlineShop_Framework_IPriceSystem
     */
    protected  function getPriceSystem() {
        return $this->priceSystem;
    }

     /**
      * @throws OnlineShop_Framework_Exception_UnsupportedException
      * @return OnlineShop_Framework_IPrice
      */
     public function getPrice() {
         throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__ . " is not supported for " . get_class($this));
     }

     /**
      * @throws OnlineShop_Framework_Exception_UnsupportedException
      * @return OnlineShop_Framework_IPrice
      */
     public function getTotalPrice() {
         throw new OnlineShop_Framework_Exception_UnsupportedException(__METHOD__ . " is not supported for " . get_class($this));
     }

     public function setProduct(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product) {
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