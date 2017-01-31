<?php

namespace AppBundle\Controller;

use PimcoreBundle\Controller\Zend\ZendController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Zend\View\Model\ViewModel;

class ZendViewController extends ZendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
        $this->container->get('logger')->debug(__METHOD__);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function postDispatch(FilterResponseEvent $event)
    {
        $this->container->get('logger')->debug(__METHOD__);
    }

    public function contentAction()
    {
        $this->enableLayout('AppBundle:ZendView:layout.phtml');
    }

    /**
     * @Route("/zf-default")
     */
    public function defaultAction()
    {
        $this->view->foo = 'bar';
        $this->view->baz = 'inga';
    }

    /**
     * @Route("/zf-default-layout")
     */
    public function defaultLayoutAction()
    {
        $this->enableLayout('AppBundle:ZendView:layout.phtml');
        $this->view->setTemplate('AppBundle:ZendView:default.phtml');
        $this->view->john = 'doe';
    }

    /**
     * @Route("/zf-extra-view")
     */
    public function extraViewAction()
    {
        // render a completely custom view model (independent of instance view);
        $view = new ViewModel();
        $view->setVariables($this->view->getVariables());
        $view->setTemplate('AppBundle:ZendView:extra-view.phtml');

        return $view;
    }

    /**
     * @Route("/zf-direct-render")
     */
    public function directRenderAction()
    {
        // render template directly
        return $this->render('AppBundle:ZendView:direct-render.phtml', [
            '_layout' => 'AppBundle:ZendView:layout.phtml',
            'foo' => 'bar'
        ]);
    }
}
