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

namespace Pimcore\Bundle\JobExecutionEngineBundle\EventSubscriber;

use Pimcore\Bundle\JobExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Messenger\Messages\JobExecutionEngineMessageInterface;
use Pimcore\Bundle\JobExecutionEngineBundle\Utils\Traits\ThrowableChainTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

/**
 * @internal
 */
final class JobExecutionSubscriber implements EventSubscriberInterface
{
    use ThrowableChainTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onWorkerMessageFailed',
            WorkerMessageHandledEvent::class => 'onWorkerMessageHandled',
        ];
    }

    public function __construct(
        protected JobExecutionAgentInterface $jobExecutionAgent
    ) {
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof JobExecutionEngineMessageInterface) {
            return;
        }

        if ($event->willRetry()) {
            return;
        }

        $this->jobExecutionAgent->jobExecutionFailed($message, $this->getFirstThrowable($event->getThrowable()));
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof JobExecutionEngineMessageInterface) {
            return;
        }

        $this->jobExecutionAgent->continueJobStepExecution($message);
    }
}
