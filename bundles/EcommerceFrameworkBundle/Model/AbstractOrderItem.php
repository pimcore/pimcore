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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;

/**
 * Abstract base class for order item pimcore objects
 */
abstract class AbstractOrderItem extends Concrete
{
    /**
     * @return CheckoutableInterface
     */
    abstract public function getProduct();

    /**
     * @param CheckoutableInterface $product
     */
    abstract public function setProduct($product);

    /**
     * @return string
     */
    abstract public function getProductNumber();

    /**
     * @param string $productNumber
     */
    abstract public function setProductNumber($productNumber);

    /**
     * @return string
     */
    abstract public function getProductName();

    /**
     * @param string $productName
     */
    abstract public function setProductName($productName);

    /**
     * @return float
     */
    abstract public function getAmount();

    /**
     * @param float $amount
     */
    abstract public function setAmount($amount);

    /**
     * @return float
     */
    abstract public function getTotalPrice();

    /**
     * @param float $totalPrice
     */
    abstract public function setTotalPrice($totalPrice);

    /**
     * @return float
     */
    abstract public function getTotalNetPrice();

    /**
     * @param float $totalNetPrice
     */
    abstract public function setTotalNetPrice($totalNetPrice);

    /**
     * @return array
     */
    abstract public function getTaxInfo();

    /**
     * @param array $taxInfo
     */
    abstract public function setTaxInfo($taxInfo);

    /**
     * @return AbstractOrderItem[]
     */
    abstract public function getSubItems();

    /**
     * @param AbstractOrderItem[] $subItems
     */
    abstract public function setSubItems($subItems);

    /**
     * @return Fieldcollection
     */
    abstract public function getPricingRules();

    /**
     * @param Fieldcollection $pricingRules
     *
     * @return $this
     */
    abstract public function setPricingRules($pricingRules);

    /**
     * @return string
     */
    abstract public function getOrderState();

    /**
     * @param string $orderState
     *
     * @return $this
     */
    abstract public function setOrderState($orderState);

    /**
     * @return string
     */
    abstract public function getComment();

    /**
     * @param string $comment
     *
     * @return $this
     */
    abstract public function setComment($comment);

    /**
     * is the order item cancel able
     *
     * @return bool
     */
    public function isCancelAble()
    {
        return !$this->isCanceled();
    }

    /**
     * is the order item edit able
     *
     * @return bool
     */
    public function isEditAble()
    {
        return !$this->isCanceled();
    }

    /**
     * ist eine rückerstattung erlaubt
     *
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
        while (!$parent instanceof AbstractOrder) {
            $parent = $parent->getParent();
        }

        return $parent;
    }
}
