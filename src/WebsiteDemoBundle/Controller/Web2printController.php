<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreZendBundle\Controller\ZendController;
use Symfony\Component\HttpFoundation\Request;

class Web2printController extends ZendController
{
    public function defaultAction()
    {
    }

    public function containerAction(Request $request)
    {
        //TODO check, if this works all the time
        $document = $request->get("document");
        $allChildren = $this->document->getAllChildren();

        $this->view->allChildren = $allChildren;
    }
}
