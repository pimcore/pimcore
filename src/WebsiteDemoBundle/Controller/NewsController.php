<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Pimcore\Model\Object\News;
use Symfony\Component\HttpFoundation\Request;
use Zend\Paginator\Paginator;

class NewsController extends ZendController
{
    public function indexAction(Request $request)
    {
        // get a list of news objects and order them by date
        $newsList = new News\Listing();
        $newsList->setOrderKey("date");
        $newsList->setOrder("DESC");

        $paginator = new Paginator($newsList);
        $paginator->setCurrentPageNumber($request->get('page'));
        $paginator->setItemCountPerPage(5);

        $this->view->news = $paginator;
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
