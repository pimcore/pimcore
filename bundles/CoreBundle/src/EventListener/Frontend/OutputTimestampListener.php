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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore;
use Pimcore\Http\Request\Resolver\OutputTimestampResolver;
use Pimcore\Tool\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class OutputTimestampListener implements EventSubscriberInterface
{
    const TIMESTAMP_OVERRIDE_PARAM_NAME = 'pimcore_override_output_timestamp';

    public function __construct(protected OutputTimestampResolver $outputTimestampResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($overrideTimestamp = (int)$event->getRequest()->query->get(self::TIMESTAMP_OVERRIDE_PARAM_NAME)) {
            if (Pimcore::inDebugMode() || Authentication::authenticateSession($event->getRequest())) {
                $this->outputTimestampResolver->setOutputTimestamp($overrideTimestamp);
            }
        }
    }
}
