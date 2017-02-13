<?php

namespace Pimcore\Bundle\PimcoreZendBundle\EventListener;

use Pimcore\Bundle\PimcoreZendBundle\Controller\EventedControllerInterface;
use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendControllerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventedControllerListener implements EventSubscriberInterface
{
    /**
     * Calls preDispatch()
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $callable = $event->getController();
        if (!is_array($callable)) {
            return;
        }

        $request    = $event->getRequest();
        $controller = $callable[0];

        if ($controller instanceof EventedControllerInterface) {
            $request->attributes->set('_evented_controller', $controller);
            $controller->preDispatch($event);
        }
    }

    /**
     * Calls postDispatch()
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request    = $event->getRequest();
        $controller = $request->attributes->get('_evented_controller');

        if (!$controller || !($controller instanceof EventedControllerInterface)) {
            return;
        }

        $controller->postDispatch($event);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE   => 'onKernelResponse'
        ];
    }
}
