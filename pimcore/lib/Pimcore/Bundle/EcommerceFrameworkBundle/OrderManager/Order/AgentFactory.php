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

    public function __construct(
        IEnvironment $environment,
        IPaymentManager $paymentManager
    )
    {
        $this->environment    = $environment;
        $this->paymentManager = $paymentManager;
    }

    public function createAgent(AbstractOrder $order): IOrderAgent
    {
        return new Agent($order, $this->environment, $this->paymentManager);
    }
}
