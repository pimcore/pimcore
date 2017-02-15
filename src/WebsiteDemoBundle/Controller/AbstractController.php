<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Controller\FrontendController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

abstract class AbstractController extends FrontendController
{
    /**
     * @inheritDoc
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // enable view auto-rendering
        $this->setViewAutoRender($event->getRequest(), true, 'php');
    }
}
