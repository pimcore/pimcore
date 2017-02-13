<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Controller;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

interface EventedControllerInterface
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event);

    /**
     * @param FilterResponseEvent $event
     */
    public function postDispatch(FilterResponseEvent $event);
}
