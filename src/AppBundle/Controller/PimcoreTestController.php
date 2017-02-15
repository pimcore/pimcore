<?php

namespace AppBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\TemplatePhp;
use Pimcore\Bundle\PimcoreBundle\Controller\FrontendController;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Model\Document;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class PimcoreTestController extends FrontendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // enable view auto-rendering
        $this->setViewAutoRender($event->getRequest(), true, 'php');

        $this->container->get('logger')->debug(__METHOD__);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
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
     * @Route("/zf-default")
     */
    public function defaultAction()
    {
        $this->view->foo = 'bar';
        $this->view->baz = 'inga';

        $this->enableViewAutoRender();
    }

    /**
     * @TemplatePhp("AppBundle:PimcoreTest:default.html.php")
     * @Route("/zf-default-layout")
     */
    public function defaultLayoutAction()
    {
        $this->view->_layout = 'AppBundle:PimcoreTest:layout.html.php';
        $this->view->john    = 'doe';
    }

    /**
     * @TemplatePhp("AppBundle:PimcoreTest:extraView.html.php")
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
     *
     * @param Request $request
     * @param Document $document
     *
     * @return Response
     */
    public function directRenderAction(Request $request, Document $document)
    {
        // render template directly
        return $this->render($this->getTemplateReference($request, 'php'), [
            '_layout'  => 'AppBundle:PimcoreTest:layout.html.php',
            'foo'      => 'bar',
            'document' => $document
        ]);
    }
}
