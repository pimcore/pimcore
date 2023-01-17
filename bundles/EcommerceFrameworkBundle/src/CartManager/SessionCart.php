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

use Pimcore\Bundle\EcommerceFrameworkBundle\EventListener\SessionBagListener;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class SessionCart extends AbstractCart implements CartInterface
{
    /**
     * @var SessionCart[]|null
     */
    protected static ?array $unserializedCarts = null;

    protected function getCartItemClassName(): string
    {
        return SessionCartItem::class;
    }

    protected function getCartCheckoutDataClassName(): string
    {
        return SessionCartCheckoutData::class;
    }

    protected static function getSessionBag(): AttributeBagInterface
    {
        try {
            $session = \Pimcore::getContainer()->get('request_stack')->getSession();
        } catch (SessionNotFoundException $e) {
            trigger_deprecation('pimcore/pimcore', '10.5',
                sprintf('Session used with non existing request stack in %s, that will not be possible in Pimcore 11.', __CLASS__));

            $session = \Pimcore::getContainer()->get('session');
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $session->getBag(SessionBagListener::ATTRIBUTE_BAG_CART);

        if (empty($sessionBag->get('carts'))) {
            $sessionBag->set('carts', []);
        }

        return $sessionBag;
    }

    public function save(): void
    {
        $session = static::getSessionBag();

        if (!$this->getId()) {
            $this->setId(uniqid('sesscart_'));
        }

        $carts = $session->get('carts');
        $carts[$this->getId()] = serialize($this);

        $session->set('carts', $carts);
    }

    /**
     * @return void
     *
     * @throws \Exception if the cart is not yet saved.
     */
    public function delete(): void
    {
        $session = static::getSessionBag();

        if (!$this->getId()) {
            throw new \Exception('Cart saved not yet.');
        }

        $this->clear();

        $carts = $session->get('carts');
        unset($carts[$this->getId()]);

        $session->set('carts', $carts);
    }

    public function sortItems(callable $value_compare_func): static
    {
        if (is_array($this->items)) {
            uasort($this->items, $value_compare_func);
        }

        return $this;
    }

    public static function getById(int $id): ?SessionCart
    {
        $carts = static::getAllCartsForUser(-1);

        return $carts[$id] ?? null;
    }

    /**
     * @static
     *
     * @param int $userId
     *
     * @return SessionCart[]
     */
    public static function getAllCartsForUser(int $userId): array
    {
        if (null === static::$unserializedCarts) {
            static::$unserializedCarts = [];

            foreach (static::getSessionBag()->get('carts') as $serializedCart) {
                $cart = unserialize($serializedCart);
                static::$unserializedCarts[$cart->getId()] = $cart;
            }
        }

        return static::$unserializedCarts;
    }

    /**
     * @return array
     *
     * @internal
     */
    public function __sleep(): array
    {
        $vars = parent::__sleep();

        $blockedVars = ['creationDate', 'modificationDate', 'priceCalculator'];

        $finalVars = [];
        foreach ($vars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * modified flag needs to be set
     *
     * @internal
     */
    public function __wakeup(): void
    {
        $timestampBackup = $this->getModificationDate();

        // set current cart
        foreach ($this->getItems() as $item) {
            $item->setCart($this);

            if ($item->getSubItems()) {
                foreach ($item->getSubItems() as $subItem) {
                    $subItem->setCart($this);
                }
            }
        }
        $this->modified();

        $this->setModificationDate($timestampBackup);
    }
}
