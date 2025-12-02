<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle\EventSubscriber;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Traits\ThrowableChainTrait;
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
        private readonly JobExecutionAgentInterface $jobExecutionAgent
    ) {
    }

    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof GenericExecutionEngineMessageInterface) {
            return;
        }

        if ($event->willRetry()) {
            return;
        }

        $throwable = $this->getFirstThrowable($event->getThrowable());
        $this->jobExecutionAgent->continueJobMessageExecution($message, $throwable);
    }

    public function onWorkerMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof GenericExecutionEngineMessageInterface) {
            return;
        }

        $this->jobExecutionAgent->continueJobMessageExecution($message);
    }
}
