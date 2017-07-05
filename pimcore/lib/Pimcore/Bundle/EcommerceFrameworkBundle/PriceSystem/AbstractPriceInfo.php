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
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;

class AbstractPriceInfo implements IPriceInfo
{
    /**
     * @var IPriceSystem
     */
    private $priceSystem;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * @var ICheckoutable
     */
    protected $product;

    /**
     * @var ICheckoutable[]
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
    public function setPriceSystem(IPriceSystem $priceSystem)
    {
        $this->priceSystem = $priceSystem;
    }

    /**
     * @return IPriceSystem
     */
    protected function getPriceSystem()
    {
        return $this->priceSystem;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): IPrice
    {
        throw new UnsupportedException(__METHOD__ . ' is not supported for ' . get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function getTotalPrice(): IPrice
    {
        throw new UnsupportedException(__METHOD__ . ' is not supported for ' . get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function setProduct(ICheckoutable $product)
    {
        $this->product = $product;
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
