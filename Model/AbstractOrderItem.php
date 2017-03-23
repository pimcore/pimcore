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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Logger;

/**
 * Abstract base class for order item pimcore objects
 */
class AbstractOrderItem extends \Pimcore\Model\Object\Concrete {

    /**
     * @throws UnsupportedException
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable
     */
    public function getProduct() {
        throw new UnsupportedException("getProduct is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $product
     * @throws UnsupportedException
     */
    public function setProduct($product) {
        throw new UnsupportedException("setProduct is not implemented for " . get_class($this));
    }


    /**
     * @throws UnsupportedException
     * @return string
     */
    public function getProductNumber() {
        throw new UnsupportedException("getProductNumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $productNumber
     * @throws UnsupportedException
     */
    public function setProductNumber($productNumber) {
        throw new UnsupportedException("setProductNumber is not implemented for " . get_class($this));
    }


    /**
     * @throws UnsupportedException
     * @return string
     */
    public function getProductName() {
        throw new UnsupportedException("getProductName is not implemented for " . get_class($this));
    }

    /**
     * @param string $productName
     * @throws UnsupportedException
     */
    public function setProductName($productName) {
        throw new UnsupportedException("setProductName is not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @return float
     */
    public function getAmount() {
        throw new UnsupportedException("getAmount is not implemented for " . get_class($this));
    }

    /**
     * @param float $amount
     * @throws UnsupportedException
     */
    public function setAmount($amount) {
        throw new UnsupportedException("setAmount is not implemented for " . get_class($this));
    }


    /**
     * @throws UnsupportedException
     * @return float
     */
    public function getTotalPrice() {
        throw new UnsupportedException("getTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalPrice($totalPrice) {
        throw new UnsupportedException("setTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @return float
     */
    public function getTotalNetPrice() {
        //prevent throwing an exception for backward compatibility
        Logger::err("getTotalNetPrice not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @param float $totalNetPrice
     */
    public function setTotalNetPrice($totalNetPrice) {
        //prevent throwing an exception for backward compatibility
        Logger::err("setTotalNetPrice not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @return array
     */
    public function getTaxInfo() {
        //prevent throwing an exception for backward compatibility
        Logger::err("getTaxInfo not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @param array $taxInfo
     */
    public function setTaxInfo($taxInfo) {
        //prevent throwing an exception for backward compatibility
        Logger::err("setTaxInfo not implemented for " . get_class($this));
    }


    /**
     * @return AbstractOrderItem[]
     * @throws UnsupportedException
     */
    public function getSubItems() {
        throw new UnsupportedException("getSubItems is not implemented for " . get_class($this));
    }

    /**
     * @param AbstractOrderItem[] $subItems
     * @throws UnsupportedException
     */
    public function setSubItems($subItems) {
        throw new UnsupportedException("setSubItems is not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getPricingRules() {
        throw new UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection $pricingRules
     * @throws UnsupportedException
     * @return $this
     */
    public function setPricingRules ($pricingRules) {
        throw new UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @throws UnsupportedException
     * @return string
     */
    public function getOrderState() {
        throw new UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $orderState
     * @throws UnsupportedException
     * @return $this
     */
    public function setOrderState ($orderState) {
        throw new UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * is the order item cancel able
     * @return bool
     */
    public function isCancelAble()
    {
        return true && !$this->isCanceled();
    }

    /**
     * is the order item edit able
     * @return bool
     */
    public function isEditAble()
    {
        return true && !$this->isCanceled();
    }


    /**
     * ist eine rückerstattung erlaubt
     * @return bool
     */
    public function isComplaintAble()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getOrderState() == AbstractOrder::ORDER_STATE_CANCELLED;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder()
    {
        $parent = $this;
        while(!$parent instanceof AbstractOrder)
        {
            $parent = $parent->getParent();
        }

        return $parent;
    }
}
