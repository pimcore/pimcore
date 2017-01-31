<?php

namespace PimcoreBundle\Controller\Zend;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Zend\View\Model\ModelInterface;

interface ZendControllerInterface
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event);

    /**
     * @param FilterResponseEvent $event
     */
    public function postDispatch(FilterResponseEvent $event);

    /**
     * @param ModelInterface $view
     * @return $this
     */
    public function setView(ModelInterface $view);

    /**
     * @return ModelInterface
     */
    public function getView();

    /**
     * @return null|ModelInterface
     */
    public function getLayout();
}
