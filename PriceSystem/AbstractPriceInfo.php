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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem;

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
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceSystem
     */
    private $priceSystem;


     /** @var int */
     protected $quantity;

     /**
      * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable
      */
     protected $product;

     /**
      * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable[]
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
      * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException
      * @return IPrice
      */
     public function getPrice() {
         throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException(__METHOD__ . " is not supported for " . get_class($this));
     }

     /**
      * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException
      * @return IPrice
      */
     public function getTotalPrice() {
         throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException(__METHOD__ . " is not supported for " . get_class($this));
     }

     public function setProduct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $product) {
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