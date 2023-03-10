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

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Psr\Log\LoggerInterface;

class MultiCartManager implements CartManagerInterface
{
    protected EnvironmentInterface $environment;

    protected CartFactoryInterface $cartFactory;

    protected CartPriceCalculatorFactoryInterface $cartPriceCalculatorFactory;

    protected OrderManagerLocatorInterface $orderManagers;

    protected LoggerInterface $logger;

    /**
     * @var CartInterface[]
     */
    protected array $carts = [];

    protected bool $initialized = false;

    public function __construct(
        EnvironmentInterface $environment,
        CartFactoryInterface $cartFactory,
        CartPriceCalculatorFactoryInterface $cartPriceCalculatorFactory,
        OrderManagerLocatorInterface $orderManagers,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->cartFactory = $cartFactory;
        $this->cartPriceCalculatorFactory = $cartPriceCalculatorFactory;
        $this->orderManagers = $orderManagers;
        $this->logger = $logger;
    }

    public function getCartClassName(): string
    {
        return $this->cartFactory->getCartClassName($this->environment);
    }

    /**
     * checks if cart manager is initialized and if not, do so
     */
    protected function checkForInit(): void
    {
        if (!$this->initialized) {
            $this->initSavedCarts();
            $this->initialized = true;
        }
    }

    protected function initSavedCarts(): void
    {
        $carts = $this->getAllCartsForCurrentUser();

        if (empty($carts)) {
            $this->carts = [];
        } else {
            foreach ($carts as $cart) {
                // check for order state of cart - remove it, when corresponding order is already committed
                $order = $this->orderManagers->getOrderManager()->getOrderFromCart($cart);
                if (empty($order) || $order->getOrderState() !== $order::ORDER_STATE_COMMITTED) {
                    $this->carts[$cart->getId()] = $cart;
                } else {
                    // cart is already committed - cleanup cart and environment
                    $this->logger->warning('Deleting cart with id {cartId} because linked order {orderId} is already committed.', [
                        'cartId' => $cart->getId(),
                        'orderId' => $order->getId(),
                    ]);

                    $cart->delete();

                    $this->environment->removeCustomItem(CheckoutManager::CURRENT_STEP . '_' . $cart->getId());
                    $this->environment->save();
                }
            }
        }
    }

    /**
     * @return CartInterface[]|null
     */
    protected function getAllCartsForCurrentUser(): ?array
    {
        $classname = $this->getCartClassName();

        return $classname::getAllCartsForUser($this->environment->getCurrentUserId());
    }

    /**
     * @param CheckoutableInterface $product
     * @param int $count
     * @param string|null $key
     * @param string|null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param string|null $comment
     *
     * @return string
     *
     * @throws InvalidConfigException
     */
    public function addToCart(
        CheckoutableInterface $product,
        int $count,
        string $key = null,
        string $itemKey = null,
        bool $replace = false,
        array $params = [],
        array $subProducts = [],
        string $comment = null
    ): string {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        $cart = $this->carts[$key];

        $itemKey = $cart->addItem($product, $count, $itemKey, $replace, $params, $subProducts, $comment);
        $this->save();

        return $itemKey;
    }

    public function save(): mixed
    {
        $this->checkForInit();

        foreach ($this->carts as $cart) {
            $cart->save();
        }

        return $this;
    }

    /**
     * @param string|null $key
     */
    public function deleteCart(string $key = null): void
    {
        $this->checkForInit();

        $this->getCart($key)->delete();
        unset($this->carts[$key]);
    }

    /**
     * @param array $params
     *
     * @return int|string
     *
     * @throws InvalidConfigException
     */
    public function createCart(array $params): int|string
    {
        $this->checkForInit();

        $cartId = $params['id'] ?? null;

        if ($cartId && isset($this->carts[$cartId])) {
            throw new InvalidConfigException('Cart with id ' . $params['id'] . ' already exists.');
        }

        if (!isset($params['name'])) {
            throw new InvalidConfigException('Cart name is missing');
        }

        $cart = $this->cartFactory->create($this->environment, (string)$params['name'], $cartId, $params);
        $cart->save();

        $this->carts[$cart->getId()] = $cart;

        return $cart->getId();
    }

    /**
     * @param string|null $key
     *
     * @throws InvalidConfigException
     */
    public function clearCart(string $key = null): void
    {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        $cart = $this->carts[$key];

        $newCart = $this->cartFactory->create($this->environment, $cart->getName(), $cart->getId());
        $this->carts[$key] = $newCart;
    }

    /**
     * @param string|null $key
     *
     * @return CartInterface
     *
     * @throws InvalidConfigException
     */
    public function getCart(string $key = null): CartInterface
    {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        return $this->carts[$key];
    }

    public function getCartByName(string $name): ?CartInterface
    {
        $this->checkForInit();

        foreach ($this->carts as $cart) {
            if ($cart->getName() === $name) {
                return $cart;
            }
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return CartInterface
     *
     * @throws InvalidConfigException
     */
    public function getOrCreateCartByName(string $name): CartInterface
    {
        $cart = $this->getCartByName($name);

        if (empty($cart)) {
            $cartKey = $this->createCart([
                'name' => $name,
            ]);
            $cart = $this->getCart($cartKey);
        }

        return $cart;
    }

    /**
     * @return CartInterface[]
     */
    public function getCarts(): array
    {
        $this->checkForInit();

        return $this->carts;
    }

    /**
     * @param string $itemKey
     * @param string|null $key
     *
     * @throws InvalidConfigException
     */
    public function removeFromCart(string $itemKey, string $key = null): void
    {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        $cart = $this->carts[$key];
        $cart->removeItem($itemKey);
    }

    public function getCartPriceCalculator(CartInterface $cart): CartPriceCalculatorInterface
    {
        return $this->cartPriceCalculatorFactory->create($this->environment, $cart);
    }

    public function reset(): void
    {
        $this->carts = [];
        $this->initialized = false;
    }
}
