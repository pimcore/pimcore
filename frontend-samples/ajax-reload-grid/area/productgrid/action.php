<?php

class Document_Tag_Area_ProductGrid extends Document_Tag_Area_Abstract {

    public function action() {
        /**
         * @var $filterDefinition Object_FilterDefinition
         */
        $filterDefinition = $this->view->href("productFilter")->getElement();
        $this->view->filterDefinitionObject = $filterDefinition;
    }
}