<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

class Transaction extends AbstractData
{
    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $affiliation;

    /**
     * @var float|null
     */
    protected $total;

    /**
     * @var float|null
     */
    protected $subTotal;

    /**
     * @var float|null
     */
    protected $tax;

    /**
     * @var float|null
     */
    protected $shipping;

    /**
     * @var string|null
     */
    protected $coupon;

    /**
     * @var string|null
     */
    protected $list;

    /**
     * @var string|null
     */
    protected $step;

    /**
     * @var string|null
     */
    protected $option;

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    /**
     * @param string $affiliation
     *
     * @return $this
     */
    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param float $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getSubTotal()
    {
        return $this->subTotal;
    }

    /**
     * @param float $subTotal
     *
     * @return $this
     */
    public function setSubTotal($subTotal)
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param float $tax
     *
     * @return $this
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @param float $shipping
     *
     * @return $this
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param string $coupon
     *
     * @return $this
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param string $list
     *
     * @return $this
     */
    public function setList($list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param string $step
     *
     * @return $this
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param string $option
     *
     * @return $this
     */
    public function setOption($option)
    {
        $this->option = $option;

        return $this;
    }
}
