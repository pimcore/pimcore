<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class AbstractController extends ZendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
        // only enable layout for initial requests
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            $this->enableLayout('WebsiteDemoBundle::layout.phtml');
        }
    }
}
