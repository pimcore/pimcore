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

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class SendConfirmationMailEvent extends Event
{
    use ArgumentsAwareTrait;

    protected CommitOrderProcessorInterface $commitOrderProcessor;

    protected AbstractOrder $order;

    protected string $confirmationMailConfig;

    protected bool $skipDefaultBehaviour = false;

    /**
     * SendConfirmationMailEvent constructor.
     *
     * @param CommitOrderProcessorInterface $commitOrderProcessor
     * @param AbstractOrder $order
     * @param string $confirmationMailConfig
     */
    public function __construct(CommitOrderProcessorInterface $commitOrderProcessor, AbstractOrder $order, string $confirmationMailConfig)
    {
        $this->commitOrderProcessor = $commitOrderProcessor;
        $this->order = $order;
        $this->confirmationMailConfig = $confirmationMailConfig;
    }

    public function getCommitOrderProcessor(): CommitOrderProcessorInterface
    {
        return $this->commitOrderProcessor;
    }

    public function setCommitOrderProcessor(CommitOrderProcessorInterface $commitOrderProcessor): void
    {
        $this->commitOrderProcessor = $commitOrderProcessor;
    }

    public function getOrder(): AbstractOrder
    {
        return $this->order;
    }

    public function setOrder(AbstractOrder $order): void
    {
        $this->order = $order;
    }

    public function getConfirmationMailConfig(): string
    {
        return $this->confirmationMailConfig;
    }

    public function setConfirmationMailConfig(string $confirmationMailConfig): void
    {
        $this->confirmationMailConfig = $confirmationMailConfig;
    }

    public function doSkipDefaultBehaviour(): bool
    {
        return $this->skipDefaultBehaviour;
    }

    public function setSkipDefaultBehaviour(bool $skipDefaultBehaviour): void
    {
        $this->skipDefaultBehaviour = $skipDefaultBehaviour;
    }
}
