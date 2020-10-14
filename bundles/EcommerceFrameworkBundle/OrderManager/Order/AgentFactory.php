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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order;

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentFactory implements OrderAgentFactoryInterface
{
    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var PaymentManagerInterface
     */
    protected $paymentManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string
     */
    protected $agentClass = Agent::class;

    public function __construct(
        EnvironmentInterface $environment,
        PaymentManagerInterface $paymentManager,
        EventDispatcherInterface $eventDispatcher,
        array $options = []
    ) {
        $this->environment = $environment;
        $this->paymentManager = $paymentManager;
        $this->eventDispatcher = $eventDispatcher;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        if (isset($options['agent_class'])) {
            if (!class_exists($options['agent_class'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Order agent class "%s" does not exist',
                    $options['agent_class']
                ));
            }

            $this->agentClass = $options['agent_class'];
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('agent_class');
        $resolver->setAllowedTypes('agent_class', 'string');
    }

    public function createAgent(AbstractOrder $order): OrderAgentInterface
    {
        $class = $this->agentClass;

        return new $class($order, $this->environment, $this->paymentManager, $this->eventDispatcher);
    }
}
