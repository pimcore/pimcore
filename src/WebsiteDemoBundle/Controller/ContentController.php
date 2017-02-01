<?php

namespace WebsiteDemoBundle\Controller;

use PimcoreZendBundle\Controller\ZendController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ContentController extends ZendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
        $this->enableLayout('WebsiteDemoBundle::layout.phtml');
    }

    public function defaultAction()
    {
    }

    public function portalAction()
    {
        $this->view->setTemplate('WebsiteDemoBundle:Content:default.phtml');
        $this->view->isPortal = true;
    }
}
