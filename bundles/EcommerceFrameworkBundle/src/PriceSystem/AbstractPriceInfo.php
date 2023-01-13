<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;

class AbstractPriceInfo implements PriceInfoInterface
{
    private PriceSystemInterface $priceSystem;

    protected int $quantity;

    protected ?CheckoutableInterface $product = null;

    /**
     * @var CheckoutableInterface[]
     */
    protected array $products;

    public static function getInstance(): static
    {
        return new static(func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setQuantity(int|string $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity(): int|string
    {
        return $this->quantity;
    }

    /**
     * {@inheritdoc}
     */
    public function isMinPrice(): bool
    {
        return $this->getQuantity() === self::MIN_PRICE;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriceSystem(PriceSystemInterface $priceSystem): static
    {
        $this->priceSystem = $priceSystem;

        return $this;
    }

    protected function getPriceSystem(): PriceSystemInterface
    {
        return $this->priceSystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(): PriceInterface
    {
        throw new UnsupportedException(__METHOD__ . ' is not supported for ' . get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalPrice(): PriceInterface
    {
        throw new UnsupportedException(__METHOD__ . ' is not supported for ' . get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function setProduct(CheckoutableInterface $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct(): ?CheckoutableInterface
    {
        return $this->product;
    }

    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function getProducts(): array
    {
        return $this->products;
    }
}
