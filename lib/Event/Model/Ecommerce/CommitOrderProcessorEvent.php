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

class CommitOrderProcessorEvent extends Event
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
     * CommitOrderProcessorEvent constructor.
     *
     * @param CommitOrderProcessorInterface $commitOrderProcessor
     * @param AbstractOrder|null $order
     * @param array $arguments
     */
    public function __construct(CommitOrderProcessorInterface $commitOrderProcessor, ?AbstractOrder $order, array $arguments = [])
    {
        $this->commitOrderProcessor = $commitOrderProcessor;
        $this->order = $order;
        $this->arguments = $arguments;
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
    public function getOrder(): ?AbstractOrder
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
}
