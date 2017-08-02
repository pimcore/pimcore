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

use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderAgent;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderAgentFactory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IPaymentManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentFactory implements IOrderAgentFactory
{
    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * @var IPaymentManager
     */
    protected $paymentManager;

    /**
     * @var string
     */
    protected $agentClass = Agent::class;

    public function __construct(
        IEnvironment $environment,
        IPaymentManager $paymentManager,
        array $options = []
    )
    {
        $this->environment    = $environment;
        $this->paymentManager = $paymentManager;

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

    public function createAgent(AbstractOrder $order): IOrderAgent
    {
        $class = $this->agentClass;

        return new $class($order, $this->environment, $this->paymentManager);
    }
}
