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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\KernelResponseEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventedControllerListener implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $callable = $event->getController();
        if (!is_array($callable)) {
            return;
        }

        $request = $event->getRequest();
        $controller = $callable[0];

        /** @TODO: Remove in Pimcore 7 */
        if ($controller instanceof EventedControllerInterface) {
            $request->attributes->set('_evented_controller', $controller);
            $controller->onKernelController($event);
        }

        if ($controller instanceof KernelControllerEventInterface) {
            $request->attributes->set('_event_controller', $controller);
            $controller->onKernelControllerEvent($event);
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $eventedController = $request->attributes->get('_evented_controller');
        $eventController = $request->attributes->get('_event_controller');

        if (!$eventedController && !$eventController) {
            return;
        }

        /** @TODO: Remove in Pimcore 7 */
        if ($eventedController instanceof EventedControllerInterface) {
            $eventedController->onKernelResponse($event);
        }

        if ($eventController instanceof KernelResponseEventInterface) {
            $eventController->onKernelResponseEvent($event);
        }
    }
}
