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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;

/**
 * Interface for cart item implementations of online shop framework
 */
interface CartItemInterface
{
    public function getProduct(): CheckoutableInterface;

    public function getCount(): int;

    public function getItemKey(): string;

    public function setProduct(CheckoutableInterface $product): void;

    public function setCount(int $count): void;

    public function setCart(CartInterface $cart): void;

    public function getCart(): ?CartInterface;

    /**
     * @return CartItemInterface[]
     */
    public function getSubItems(): array;

    /**
     * @param CartItemInterface[] $subItems
     *
     * @return void
     */
    public function setSubItems(array $subItems): void;

    public function getPrice(): PriceInterface;

    public function getTotalPrice(): PriceInterface;

    public function getPriceInfo(): PriceInfoInterface;

    public function setComment(string $comment): void;

    public function getComment(): string;

    /**
     * @return AbstractSetProductEntry[]
     */
    public function getSetEntries(): array;

    public function getAvailabilityInfo(): AvailabilityInterface;

    /**
     * @static
     *
     * @param int|string $cartId
     * @param string $itemKey
     * @param string $parentKey
     *
     * @return CartItemInterface|null
     */
    public static function getByCartIdItemKey(int|string $cartId, string $itemKey, string $parentKey = ''): ?CartItemInterface;

    /**
     * @static
     *
     * @param int|string $cartId
     */
    public static function removeAllFromCart(int|string $cartId): void;

    public function save(): void;

    /**
     * @param \DateTime|null $date
     *
     * @return void
     */
    public function setAddedDate(\DateTime $date = null): void;

    public function getAddedDate(): \DateTime;

    /**
     * @return int unix timestamp
     */
    public function getAddedDateTimestamp(): int;

    public function setAddedDateTimestamp(int $time): void;

    /**
     * get item name
     *
     * @return string
     */
    public function getName(): string;
}
