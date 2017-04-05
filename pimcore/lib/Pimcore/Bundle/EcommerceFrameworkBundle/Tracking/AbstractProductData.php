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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

abstract class AbstractProductData extends AbstractData
{
    /** @var string */
    protected $transactionId;

    /** @var string */
    protected $name;

    /** @var string */
    protected $brand;

    /** @var array */
    protected $categories;

    /** @var string */
    protected $variant;

    /** @var int */
    protected $position;

    /** @var float */
    protected $price;

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     * @return $this
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategory()
    {
        if (is_array($this->categories) && count($this->categories) > 0) {
            return $this->categories[0];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     * @return $this
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return string
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * @param string $variant
     * @return $this
     */
    public function setVariant($variant)
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }
}
