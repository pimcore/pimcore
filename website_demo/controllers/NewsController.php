<?php

use Website\Controller\Action;
use Pimcore\Model\Object;

class NewsController extends Action
{
    public function indexAction()
    {
        $this->enableLayout();

        // get a list of news objects and order them by date
        $newsList = new Object\News\Listing();
        $newsList->setOrderKey("date");
        $newsList->setOrder("DESC");

        $paginator = \Zend_Paginator::factory($newsList);
        $paginator->setCurrentPageNumber($this->getParam('page'));
        $paginator->setItemCountPerPage(5);

        $this->view->news = $paginator;
    }

    public function detailAction()
    {
        $this->enableLayout();

        // "id" is the named parameters in "Static Routes"
        $news = Object\News::getById($this->getParam("id"));

        if (!$news instanceof Object\News || !$news->isPublished()) {
            // this will trigger a 404 error response
            throw new \Zend_Controller_Router_Exception("invalid request");
        }

        $this->view->news = $news;
    }
}
