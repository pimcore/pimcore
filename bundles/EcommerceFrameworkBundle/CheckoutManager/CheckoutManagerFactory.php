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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\CancelPaymentOrRecreateOrderStrategy;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutManagerFactory implements CheckoutManagerFactoryInterface
{
    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var OrderManagerLocatorInterface
     */
    protected $orderManagers;

    /**
     * @var CommitOrderProcessorLocatorInterface
     */
    protected $commitOrderProcessors;

    /**
     * Array of checkout step definitions
     *
     * @var array
     */
    protected $checkoutStepDefinitions = [];

    /**
     * @var PaymentInterface
     */
    protected $paymentProvider;

    /**
     * @var CheckoutManagerInterface[]
     */
    protected $checkoutManagers = [];

    /**
     * @var ServiceLocator|null
     */
    protected $handlePendingPaymentStrategyLocator = null;

    /**
     * @var string
     */
    protected $className = CheckoutManager::class;

    protected $handlePendingPaymentStrategy = null;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher = null;

    public function __construct(
        EnvironmentInterface $environment,
        OrderManagerLocatorInterface $orderManagers,
        CommitOrderProcessorLocatorInterface $commitOrderProcessors,
        array $checkoutStepDefinitions,
        PaymentInterface $paymentProvider = null,
        array $options = [],
        ServiceLocator $handlePendingPaymentStrategyLocator = null,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->environment = $environment;
        $this->orderManagers = $orderManagers;
        $this->commitOrderProcessors = $commitOrderProcessors;
        $this->paymentProvider = $paymentProvider;
        $this->handlePendingPaymentStrategyLocator = $handlePendingPaymentStrategyLocator;
        $this->eventDispatcher = $eventDispatcher;

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

        if (isset($options['handle_pending_payments_strategy']) && $this->handlePendingPaymentStrategyLocator) {
            $this->handlePendingPaymentStrategy = $this->handlePendingPaymentStrategyLocator->get($options['handle_pending_payments_strategy']);
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
        $resolver->setDefined('class');
        $resolver->setAllowedTypes('class', 'string');
        $resolver->setRequired('class');

        $resolver->setDefined('options');
        $resolver->setAllowedTypes('options', ['array', 'null']);
    }

    protected function configureClassOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('class');
        $resolver->setAllowedTypes('class', 'string');

        $resolver->setDefined('handle_pending_payments_strategy');
        $resolver->setAllowedTypes('handle_pending_payments_strategy', 'string');

        $resolver->setDefault('handle_pending_payments_strategy', CancelPaymentOrRecreateOrderStrategy::class);
        $resolver->setRequired(['handle_pending_payments_strategy']);
    }

    public function createCheckoutManager(CartInterface $cart): CheckoutManagerInterface
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

        $checkoutManager = new $className(
            $cart,
            $this->environment,
            $this->orderManagers,
            $this->commitOrderProcessors,
            $checkoutSteps,
            $this->eventDispatcher,
            $this->paymentProvider
        );
        $checkoutManager->setHandlePendingPaymentsStrategy($this->handlePendingPaymentStrategy);

        $this->checkoutManagers[$cartId] = $checkoutManager;

        return $this->checkoutManagers[$cartId];
    }

    protected function buildCheckoutStep(CartInterface $cart, array $checkoutStepDefinition): CheckoutStepInterface
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
