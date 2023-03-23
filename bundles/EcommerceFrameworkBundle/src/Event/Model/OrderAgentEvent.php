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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Event\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class OrderAgentEvent extends Event
{
    use ArgumentsAwareTrait;

    protected OrderAgentInterface $orderAgent;

    /**
     * OrderAgentEvent constructor.
     *
     * @param OrderAgentInterface $orderAgent
     * @param array $arguments
     */
    public function __construct(OrderAgentInterface $orderAgent, array $arguments = [])
    {
        $this->orderAgent = $orderAgent;
        $this->arguments = $arguments;
    }

    public function getOrderAgent(): OrderAgentInterface
    {
        return $this->orderAgent;
    }

    public function setOrderAgent(OrderAgentInterface $orderAgent): void
    {
        $this->orderAgent = $orderAgent;
    }
}
