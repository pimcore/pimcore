<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;

class AbstractPriceInfo implements PriceInfoInterface
{
    /**
     * @var PriceSystemInterface
     */
    private $priceSystem;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * @var CheckoutableInterface
     */
    protected $product;

    /**
     * @var CheckoutableInterface[]
     */
    protected $products;

    /**
     * @return AbstractPriceInfo
     */
    public static function getInstance()
    {
        return new static(func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @inheritdoc
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @inheritdoc
     */
    public function isMinPrice(): bool
    {
        return $this->getQuantity() === self::MIN_PRICE;
    }

    /**
     * @inheritdoc
     */
    public function setPriceSystem(PriceSystemInterface $priceSystem)
    {
        $this->priceSystem = $priceSystem;

        return $this;
    }

    /**
     * @return PriceSystemInterface
     */
    protected function getPriceSystem()
    {
        return $this->priceSystem;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): PriceInterface
    {
        throw new UnsupportedException(__METHOD__ . ' is not supported for ' . get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function getTotalPrice(): PriceInterface
    {
        throw new UnsupportedException(__METHOD__ . ' is not supported for ' . get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function setProduct(CheckoutableInterface $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProduct()
    {
        return $this->product;
    }

    public function setProducts($products)
    {
        $this->products = $products;
    }

    public function getProducts()
    {
        return $this->products;
    }
}
