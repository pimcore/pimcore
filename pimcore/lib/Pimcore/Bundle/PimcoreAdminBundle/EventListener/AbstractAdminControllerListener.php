<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\EventListener;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminControllerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

abstract class AbstractAdminControllerListener
{
    /**
     * @param FilterControllerEvent $event
     * @return bool
     */
    protected function isAdminController(FilterControllerEvent $event)
    {
        $controller = $this->getAdminController($event);

        return $controller && $controller instanceof AdminControllerInterface;
    }

    /**
     * @param FilterControllerEvent $event
     * @return AdminControllerInterface|null
     */
    protected function getAdminController(FilterControllerEvent $event)
    {
        $callable = $event->getController();

        if (!is_array($callable) || count($callable) === 0) {
            return null;
        }

        $controller = $callable[0];
        if ($controller instanceof AdminControllerInterface) {
            return $controller;
        }
    }
}
