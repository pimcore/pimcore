<?php

namespace AppBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\TemplatePhp;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Pimcore\Model\Document;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

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

    /**
     * @TemplatePhp()
     */
    public function contentAction()
    {
    }

    /**
     * @TemplatePhp()
     * @Route("/zf-default")
     */
    public function defaultAction()
    {
        $this->view->foo = 'bar';
        $this->view->baz = 'inga';
    }

    /**
     * @TemplatePhp("AppBundle:ZendView:default.html.php")
     * @Route("/zf-default-layout")
     */
    public function defaultLayoutAction()
    {
        $this->view->_layout = 'AppBundle:ZendView:layout.html.php';
        $this->view->john = 'doe';
    }

    /**
     * @TemplatePhp("AppBundle:ZendView:extra-view.html.php")
     * @Route("/zf-extra-view")
     */
    public function extraViewAction()
    {
        // render a completely custom view model (independent of request view)
        $view = new ViewModel();
        $view->getParameters()->add($this->view->getAllParameters());

        return $view;
    }

    /**
     * @Route("/zf-direct-render")
     */
    public function directRenderAction(Document $document)
    {
        // render template directly
        return $this->render('AppBundle:ZendView:direct-render.html.php', [
            '_layout'  => 'AppBundle:ZendView:layout.html.php',
            'foo'      => 'bar',
            'document' => $document
        ]);
    }
}
