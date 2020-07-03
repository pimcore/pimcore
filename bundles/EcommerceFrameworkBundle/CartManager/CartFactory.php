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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CartFactory implements CartFactoryInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['cart_class_name']);

        $resolver->setDefaults([
            'cart_class_name' => Cart::class,
            'guest_cart_class_name' => SessionCart::class,
            'cart_readonly_mode' => AbstractCart::CART_READ_ONLY_MODE_STRICT,
        ]);

        $resolver->setAllowedTypes('cart_class_name', 'string');
        $resolver->setAllowedTypes('guest_cart_class_name', 'string');
        $resolver->setAllowedTypes('cart_readonly_mode', 'string');

        $resolver->addAllowedValues('cart_readonly_mode', [
            AbstractCart::CART_READ_ONLY_MODE_STRICT,
            AbstractCart::CART_READ_ONLY_MODE_DEACTIVATED,
        ]);
    }

    public function getCartClassName(EnvironmentInterface $environment): string
    {
        if ($environment->getUseGuestCart()) {
            return $this->options['guest_cart_class_name'];
        }

        return $this->options['cart_class_name'];
    }

    public function create(EnvironmentInterface $environment, string $name, string $id = null, array $options = []): CartInterface
    {
        $cart = $this->createCartInstance($environment, $name, $id, $options);
        $cart->save();

        return $cart;
    }

    protected function createCartInstance(EnvironmentInterface $environment, string $name, string $id = null, array $options = []): CartInterface
    {
        $class = $this->getCartClassName($environment);

        /** @var CartInterface $cart */
        $cart = new $class;

        $cart->setName($name);

        if (null !== $id) {
            $cart->setId($id);
        }

        if ($cart instanceof AbstractCart) {
            $cart->setCurrentReadonlyMode($this->options['cart_readonly_mode']);
        }

        return $cart;
    }

    /**
     * @return string
     */
    public function getCartReadOnlyMode(): string
    {
        return $this->options['cart_readonly_mode'];
    }
}
