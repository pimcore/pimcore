<?php

namespace WebsiteDemoBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\PhpTemplate;
use Pimcore\Bundle\PimcoreBundle\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PhpTemplate()
 */
class Web2printController extends FrontendController
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
