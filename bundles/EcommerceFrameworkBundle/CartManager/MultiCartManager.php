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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManagerLocator;
use Psr\Log\LoggerInterface;

class MultiCartManager implements ICartManager
{
    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * @var ICartFactory
     */
    protected $cartFactory;

    /**
     * @var ICartPriceCalculatorFactory
     */
    protected $cartPriceCalculatorFactory;

    /**
     * @var IOrderManagerLocator
     */
    protected $orderManagers;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ICart[]
     */
    protected $carts = [];

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param IEnvironment $environment
     * @param ICartFactory $cartFactory
     * @param ICartPriceCalculatorFactory $cartPriceCalculatorFactory
     * @param IOrderManagerLocator $orderManagers
     * @param LoggerInterface $logger
     */
    public function __construct(
        IEnvironment $environment,
        ICartFactory $cartFactory,
        ICartPriceCalculatorFactory $cartPriceCalculatorFactory,
        IOrderManagerLocator $orderManagers,
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
    protected function checkForInit()
    {
        if (!$this->initialized) {
            $this->initSavedCarts();
            $this->initialized = true;
        }
    }

    protected function initSavedCarts()
    {
        $classname = $this->getCartClassName();

        /* @var ICart[] $carts */
        $carts = $classname::getAllCartsForUser($this->environment->getCurrentUserId());

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
                        'orderId' => $order->getId()
                    ]);

                    $cart->delete();

                    $this->environment->removeCustomItem(CheckoutManager::CURRENT_STEP . '_' . $cart->getId());
                    $this->environment->save();
                }
            }
        }
    }

    /**
     * @param ICheckoutable $product
     * @param float $count
     * @param null $key
     * @param null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     *
     * @return null|string
     *
     * @throws InvalidConfigException
     */
    public function addToCart(
        ICheckoutable $product,
        $count,
        $key = null,
        $itemKey = null,
        $replace = false,
        array $params = [],
        array $subProducts = [],
        $comment = null
    ) {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        $cart = $this->carts[$key];

        $itemKey = $cart->addItem($product, $count, $itemKey, $replace, $params, $subProducts, $comment);
        $this->save();

        return $itemKey;
    }

    /**
     * @return void
     */
    public function save()
    {
        $this->checkForInit();

        foreach ($this->carts as $cart) {
            $cart->save();
        }
    }

    /**
     * @param null $key
     */
    public function deleteCart($key = null)
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
    public function createCart(array $params)
    {
        $this->checkForInit();

        if (array_key_exists($params['id'], $this->carts)) {
            throw new InvalidConfigException('Cart with id ' . $params['id'] . ' already exists.');
        }

        if (!isset($params['name'])) {
            throw new InvalidConfigException('Cart name is missing');
        }

        $cart = $this->cartFactory->create($this->environment, (string)$params['name'], $params['id'] ?? null, $params);
        $cart->save();

        $this->carts[$cart->getId()] = $cart;

        return $cart->getId();
    }

    /**
     * @param null $key
     *
     * @throws InvalidConfigException
     */
    public function clearCart($key = null)
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
     * @param null $key
     *
     * @return ICart
     *
     * @throws InvalidConfigException
     */
    public function getCart($key = null)
    {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        return $this->carts[$key];
    }

    /**
     * @param string $name
     *
     * @return null|ICart
     */
    public function getCartByName($name)
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
     * @return ICart[]
     */
    public function getCarts(): array
    {
        $this->checkForInit();

        return $this->carts;
    }

    /**
     * @param string $itemKey
     * @param null $key
     *
     * @throws InvalidConfigException
     */
    public function removeFromCart($itemKey, $key = null)
    {
        $this->checkForInit();

        if (empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException(sprintf('Cart %s was not found.', $key));
        }

        $cart = $this->carts[$key];
        $cart->removeItem($itemKey);
    }

    /**
     * @param ICart $cart
     *
     * @return ICartPriceCalculator
     */
    public function getCartPriceCalculator(ICart $cart): ICartPriceCalculator
    {
        return $this->cartPriceCalculatorFactory->create($this->environment, $cart);
    }

    public function reset()
    {
        $this->carts = [];
        $this->initialized = false;
    }
}
