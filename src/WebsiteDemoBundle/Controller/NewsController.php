<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Pimcore\Model\Object\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class NewsController extends ZendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
        $this->enableLayout('WebsiteDemoBundle::layout.phtml');
    }

    public function indexAction()
    {
        // get a list of news objects and order them by date
        $newsList = new News\Listing();
        $newsList->setOrderKey("date");
        $newsList->setOrder("DESC");

        $this->view->news = $newsList;

        // TODO pagination - evaluate if we want to use Zend\Paginator or something else
    }

    public function detailAction(Request $request)
    {
        // alternatively type hint the method with $id to get the route match injected
        $id = $request->get('id');

        // "id" is the named parameters in "Static Routes"
        $news = News::getById($id);

        if (!$news instanceof News || !$news->isPublished()) {
            // this will trigger a 404 error response
            throw $this->createNotFoundException('Invalid request');
        }

        $this->view->news = $news;
    }
}
