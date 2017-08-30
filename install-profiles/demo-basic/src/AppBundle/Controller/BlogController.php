<?php

namespace AppBundle\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\Request;
use Zend\Paginator\Paginator;

class BlogController extends FrontendController
{
    public function indexAction(Request $request)
    {
        // get a list of news objects and order them by date
        $blogList = new DataObject\BlogArticle\Listing();
        $blogList->setOrderKey('date');
        $blogList->setOrder('DESC');

        // selected category
        $selectedCategory = null;
        if ($selectedCategoryId = $request->get('category')) {
            $selectedCategory = DataObject\BlogCategory::getById((int)$selectedCategoryId);
            $this->view->selectedCategory = $selectedCategory;
        }

        // selected archive
        $selectedArchive = null;
        if ($selectedArchive = $request->get('archive')) {
            $this->view->selectedArchive = $selectedArchive;
        }

        $conditions = [];

        if ($selectedCategory) {
            $conditions[] = 'categories LIKE ' . $blogList->quote('%,' . $selectedCategory->getId() . ',%');
        }

        if ($request->get('archive')) {
            $conditions[] = "DATE_FORMAT(FROM_UNIXTIME(date), '%Y-%c') = " . $blogList->quote($selectedArchive);
        }

        if (!empty($conditions)) {
            $blogList->setCondition(implode(' AND ', $conditions));
        }

        // we're using Zend\Paginator here, but you can use any other paginator (e.g. Pagerfanta)
        $paginator = new Paginator($blogList);
        $paginator->setCurrentPageNumber($request->get('page'));
        $paginator->setItemCountPerPage(2);

        $this->view->articles = $paginator;

        // get all categories
        $categories = DataObject\BlogCategory::getList(); // this is an alternative way to get an object list
        $this->view->categories = $categories;

        // archive information, we have to do this in pure SQL
        $db = \Pimcore\Db::get();
        $ranges = $db->fetchCol("SELECT DATE_FORMAT(FROM_UNIXTIME(date), '%Y-%c') as ranges FROM object_5 GROUP BY DATE_FORMAT(FROM_UNIXTIME(date), '%b-%Y') ORDER BY ranges ASC");
        $this->view->archiveRanges = $ranges;

        if ($request->get('pdf')) {
            //Simple usage
            $html = $this->renderView(':Blog:index.html.php', $this->view->getAllParameters());

            return new \Symfony\Component\HttpFoundation\Response(
                \Pimcore\Web2Print\Processor::getInstance()->getPdfFromString($html),
                200,
                [
                    'Content-Type' => 'application/pdf',
                ]
            );

            //Advanced usage
            /*$params = $this->view->getAllParameters();
            $params['testPlaceholder'] = ' :-)';
            $html = $this->renderView(':Blog:index.html.php', $params);

            $adapter = \Pimcore\Web2Print\Processor::getInstance();
            //add custom settings if necessary
            if ($adapter instanceof \Pimcore\Web2Print\Processor\WkHtmlToPdf) {
                $params['adapterConfig'] = '-O landscape';
            } elseif($adapter instanceof \Pimcore\Web2Print\Processor\PdfReactor8) {
                //Config settings -> http://www.pdfreactor.com/product/doc/webservice/php.html#Configuration
                $params['adapterConfig'] = [
                    'author' => 'Max Mustermann',
                    'title' => 'Custom Title',
                    'javaScriptMode' => 0,
                    'addLinks' => true,
                    'appendLog' => true,
                    'enableDebugMode' => true
                ];
            }

            return new \Symfony\Component\HttpFoundation\Response(
                $adapter->getPdfFromString($html, $params),
                200,
                array(
                    'Content-Type' => 'application/pdf',
                    // 'Content-Disposition'   => 'attachment; filename="custom-pdf.pdf"' //direct download
                )
            );*/
        }
    }

    public function detailAction(Request $request)
    {
        // "id" is the named parameters in "Static Routes"
        $article = DataObject\BlogArticle::getById($request->get('id'));

        if (!$article instanceof DataObject\BlogArticle || !$article->isPublished()) {
            throw $this->createNotFoundException('Invalid request - no such blog article');
        }

        $this->view->article = $article;
    }

    public function sidebarBoxAction(Request $request)
    {
        $items = (int) $request->get('items');
        if (!$items) {
            $items = 3;
        }

        // this is the alternative way of getting a list of objects
        $blogList = DataObject\BlogArticle::getList([
            'limit' => $items,
            'order' => 'DESC',
            'orderKey' => 'date'
        ]);

        $this->view->articles = $blogList;
    }
}
