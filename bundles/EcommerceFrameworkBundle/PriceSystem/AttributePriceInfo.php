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

/**
 * Attribute info for attribute price system
 */
class AttributePriceInfo extends AbstractPriceInfo implements PriceInfoInterface
{
    /**
     * @var PriceInterface
     */
    protected $price;

    /**
     * @var PriceInterface
     */
    protected $totalPrice;

    public function __construct(PriceInterface $price, $quantity, PriceInterface $totalPrice)
    {
        $this->price = $price;
        $this->totalPrice = $totalPrice;
        $this->quantity = $quantity;
    }

    public function getPrice(): PriceInterface
    {
        return $this->price;
    }

    public function getTotalPrice(): PriceInterface
    {
        return $this->totalPrice;
    }

    /**
     * Try to delegate all other functions to the product
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->product->$name($arguments);
    }
}
