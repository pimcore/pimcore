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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\KernelResponseEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class EventedControllerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $callable = $event->getController();
        if (!is_array($callable)) {
            return;
        }

        $request = $event->getRequest();
        $controller = $callable[0];

        $request->attributes->set('_event_controller', $controller);

        if ($controller instanceof KernelControllerEventInterface) {
            $controller->onKernelControllerEvent($event);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $eventController = $request->attributes->get('_event_controller');

        if ($eventController instanceof KernelResponseEventInterface) {
            $eventController->onKernelResponseEvent($event);
        }
    }
}
