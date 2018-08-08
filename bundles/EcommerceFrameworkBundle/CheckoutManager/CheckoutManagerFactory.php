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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutManagerFactory implements ICheckoutManagerFactory
{
    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * @var IOrderManagerLocator
     */
    protected $orderManagers;

    /**
     * @var ICommitOrderProcessorLocator
     */
    protected $commitOrderProcessors;

    /**
     * Array of checkout step definitions
     *
     * @var array
     */
    protected $checkoutStepDefinitions = [];

    /**
     * @var IPayment
     */
    protected $paymentProvider;

    /**
     * @var ICheckoutManager[]
     */
    protected $checkoutManagers = [];

    /**
     * @var string
     */
    protected $className = CheckoutManager::class;

    public function __construct(
        IEnvironment $environment,
        IOrderManagerLocator $orderManagers,
        ICommitOrderProcessorLocator $commitOrderProcessors,
        array $checkoutStepDefinitions,
        IPayment $paymentProvider = null,
        array $options = []
    ) {
        $this->environment = $environment;
        $this->orderManagers = $orderManagers;
        $this->commitOrderProcessors = $commitOrderProcessors;
        $this->paymentProvider = $paymentProvider;

        $this->processOptions($options);
        $this->processCheckoutStepDefinitions($checkoutStepDefinitions);
    }

    protected function processOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($options);

        if (isset($options['class'])) {
            $this->className = $options['class'];
        }
    }

    protected function processCheckoutStepDefinitions(array $checkoutStepDefinitions)
    {
        $stepResolver = new OptionsResolver();
        $this->configureStepOptions($stepResolver);

        foreach ($checkoutStepDefinitions as $checkoutStepDefinition) {
            $this->checkoutStepDefinitions[] = $stepResolver->resolve($checkoutStepDefinition);
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $this->configureClassOptions($resolver);
    }

    protected function configureStepOptions(OptionsResolver $resolver)
    {
        $this->configureClassOptions($resolver);

        $resolver->setRequired('class');

        $resolver->setDefined('options');
        $resolver->setAllowedTypes('options', ['array', 'null']);
    }

    protected function configureClassOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('class');
        $resolver->setAllowedTypes('class', 'string');
    }

    public function createCheckoutManager(ICart $cart): ICheckoutManager
    {
        $cartId = $cart->getId();

        if (isset($this->checkoutManagers[$cartId])) {
            return $this->checkoutManagers[$cartId];
        }

        $checkoutSteps = [];
        foreach ($this->checkoutStepDefinitions as $checkoutStepDefinition) {
            $checkoutSteps[] = $this->buildCheckoutStep($cart, $checkoutStepDefinition);
        }

        $className = $this->className;

        $this->checkoutManagers[$cartId] = new $className(
            $cart,
            $this->environment,
            $this->orderManagers,
            $this->commitOrderProcessors,
            $checkoutSteps,
            $this->paymentProvider
        );

        return $this->checkoutManagers[$cartId];
    }

    protected function buildCheckoutStep(ICart $cart, array $checkoutStepDefinition): ICheckoutStep
    {
        $className = $checkoutStepDefinition['class'];

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf(
                'Checkout step class "%s" does not exist',
                $className
            ));
        }

        $step = new $className($cart, $checkoutStepDefinition['options'] ?? []);

        return $step;
    }
}
