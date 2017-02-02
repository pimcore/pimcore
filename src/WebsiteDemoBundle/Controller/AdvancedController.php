<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Model\Asset;
use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class AdvancedController extends ZendController
{
    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
        $this->enableLayout('WebsiteDemoBundle::layout.phtml');
    }


    public function assetThumbnailListAction()
    {

        // try to get the tag where the parent folder is specified
        $parentFolder = $this->document->getElement("parentFolder");
        if ($parentFolder) {
            $parentFolder = $parentFolder->getElement();
        }

        if (!$parentFolder) {
            // default is the home folder
            $parentFolder = Asset::getById(1);
        }

        // get all children of the parent
        $list = new Asset\Listing();
        $list->setCondition("path like ?", $parentFolder->getFullpath() . "%");

        $this->view->list = $list;


    }



}