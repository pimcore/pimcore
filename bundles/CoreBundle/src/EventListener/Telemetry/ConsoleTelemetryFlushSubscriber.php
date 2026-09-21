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
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flushes telemetry buffered during a console command to the spool on console.terminate.
 *
 * Web requests flush on kernel.terminate ({@see TelemetryFlushSubscriber}); CLI has no equivalent
 * hook, so behavioral events captured by commands would otherwise be lost. Runs at a late priority
 * so it fires after any other terminate work. A no-op when nothing was buffered or telemetry is off.
 *
 * @internal
 */
final readonly class ConsoleTelemetryFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TelemetryInterface $telemetry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -256],
        ];
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->telemetry->flush();
    }
}
