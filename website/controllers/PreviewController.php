<?php
class PreviewController extends Website_Controller_Action
{
    /**
     *
     */
    public function defaultAction()
    {
        $this->view->object = Object_Artikel::getById( $this->getParam('id') );
        $this->view->render = $this->getParam('render', 'web');
    }
}