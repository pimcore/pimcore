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

abstract class AbstractCartCheckoutData extends \Pimcore\Model\AbstractModel
{
    protected string $key;

    protected array $data;

    protected ?CartInterface $cart = null;

    public function setCart(CartInterface $cart)
    {
        $this->cart = $cart;
    }

    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    public function getCartId(): int|string|null
    {
        return $this->getCart()->getId();
    }

    abstract public function save();

    public static function getByKeyCartId($key, $cartId)
    {
        throw new \Exception('Not implemented.');
    }

    public static function removeAllFromCart(int|string $cartId)
    {
        throw new \Exception('Not implemented.');
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
