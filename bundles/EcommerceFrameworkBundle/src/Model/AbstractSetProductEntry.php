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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;

/**
 * Class for product entry of a set product - container for product and quantity
 */
class AbstractSetProductEntry
{
    private int $quantity;

    private CheckoutableInterface $product;

    public function __construct(CheckoutableInterface $product, int $quantity = 1)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function setProduct(CheckoutableInterface $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): CheckoutableInterface
    {
        return $this->product;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * returns id of set product
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->getProduct()->getId();
    }
}
