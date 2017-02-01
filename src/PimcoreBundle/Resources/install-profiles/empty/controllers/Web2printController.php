<?php

use Website\Controller\Action;

class Web2printController extends Action
{
    public function defaultAction()
    {
    }

    public function containerAction()
    {
        $document = $this->getParam("document");
        $allChildren = $document->getAllChildren();

        $this->view->allChildren = $allChildren;
    }
}
