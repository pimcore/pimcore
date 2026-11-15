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

namespace Pimcore\Bundle\CoreBundle\EventListener\Telemetry;

use Pimcore\Telemetry\TelemetryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Delivers behavioral telemetry buffered during a web request.
 *
 * Event subscribers only buffer events via {@see TelemetryInterface::capture()};
 * this listener flushes that buffer on
 * kernel.terminate, which runs after the response has been sent to the client, so the
 * relay round-trip never adds latency to the user's request. When nothing was captured
 * the flush is a no-op (empty buffer returns immediately). CLI and maintenance contexts
 * flush explicitly and do not rely on this listener.
 *
 * @internal
 */
final readonly class TelemetryFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TelemetryInterface $telemetry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->telemetry->flush();
    }
}
