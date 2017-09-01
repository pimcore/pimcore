<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class Web2printController extends FrontendController
{
    public function defaultAction()
    {
    }

    public function containerAction(Request $request)
    {
        $allChildren = $this->document->getAllChildren();

        $this->view->allChildren = $allChildren;
    }
}
