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

namespace Pimcore\Event\Model\Ecommerce;

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\Event;

class SendConfirmationMailEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var CommitOrderProcessorInterface
     */
    protected $commitOrderProcessor;

    /**
     * @var AbstractOrder
     */
    protected $order;

    /**
     * @var string
     */
    protected $confirmationMailConfig;

    /**
     * @var bool
     */
    protected $skipDefaultBehaviour = false;

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

    /**
     * @return CommitOrderProcessorInterface
     */
    public function getCommitOrderProcessor(): CommitOrderProcessorInterface
    {
        return $this->commitOrderProcessor;
    }

    /**
     * @param CommitOrderProcessorInterface $commitOrderProcessor
     */
    public function setCommitOrderProcessor(CommitOrderProcessorInterface $commitOrderProcessor): void
    {
        $this->commitOrderProcessor = $commitOrderProcessor;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder(): AbstractOrder
    {
        return $this->order;
    }

    /**
     * @param AbstractOrder $order
     */
    public function setOrder(AbstractOrder $order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getConfirmationMailConfig(): string
    {
        return $this->confirmationMailConfig;
    }

    /**
     * @param string $confirmationMailConfig
     */
    public function setConfirmationMailConfig(string $confirmationMailConfig): void
    {
        $this->confirmationMailConfig = $confirmationMailConfig;
    }

    /**
     * @return bool
     */
    public function doSkipDefaultBehaviour(): bool
    {
        return $this->skipDefaultBehaviour;
    }

    /**
     * @param bool $skipDefaultBehaviour
     */
    public function setSkipDefaultBehaviour(bool $skipDefaultBehaviour): void
    {
        $this->skipDefaultBehaviour = $skipDefaultBehaviour;
    }
}
