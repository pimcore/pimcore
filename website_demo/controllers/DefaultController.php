<?php

use Website\Controller\Action;

class DefaultController extends Action
{
    public function defaultAction()
    {
    }

    public function layout()
    {
        $this->enableLayout();
    }
}
