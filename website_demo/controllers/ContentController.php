<?php

use Website\Controller\Action;
use Pimcore\Model\Asset;

class ContentController extends Action
{
    public function defaultAction() {
        // in this example we're not using $this->enableLayout() here, instead we're enabling it in the view
        //$this->enableLayout();
    }

    public function portalAction() {
        // nothing do do here ==> see view script (website/views/scripts/content/portal.php)
    }

    public function thumbnailsAction() {
        // enabling the layout engine (Zend_Layout)
        // using this method it's not necessary to specify a layout in the view ("layout" is used as name)
        // if you're using $this->layout() in your view it's not necessary to call $this->enableLayout() here
        $this->enableLayout();
    }

    public function websiteTranslationsAction() {

    }

    public function editableRoundupAction() {

    }

    public function simpleFormAction() {

        $success = false;

        // getting parameters is very easy ... just call $this->getParam("yorParamKey"); regardless if's POST or GET
        if($this->getParam("firstname") && $this->getParam("lastname") && $this->getParam("email")) {
            $success = true;

            // of course you can store the data here into an object, or send a mail, ... do whatever you want or need
            // ...
            // ...
        }

        // do some validation & assign the parameters to the view
        foreach (["firstname", "lastname", "email"] as $key) {
            if($this->getParam($key)) {
                $this->view->$key = htmlentities(strip_tags($this->getParam($key)));
            }
        }

        // assign the status to the view
        $this->view->success = $success;
    }

    public function galleryRenderletAction() {
        if($this->getParam("id") && $this->getParam("type") == "asset") {
            $this->view->asset = Asset::getById($this->getParam("id"));
        }
    }
}
