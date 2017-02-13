<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Controller\DocumentAwareInterface;
use Pimcore\Bundle\PimcoreBundle\Controller\Traits\DocumentAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Controller\Traits\ViewAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Controller\ViewAwareInterface;
use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

abstract class ZendController extends Controller implements EventedControllerInterface, DocumentAwareInterface, ViewAwareInterface
{
    use DocumentAwareTrait;
    use ViewAwareTrait;

    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function postDispatch(FilterResponseEvent $event)
    {
    }

    /**
     * @return $this
     */
    public function disableLayout()
    {
        $this->view->getParameters()->set(PhpEngine::PARAM_NO_LAYOUT, true);

        return $this;
    }
}
