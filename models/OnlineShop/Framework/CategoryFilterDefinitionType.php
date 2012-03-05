<?php


abstract class OnlineShop_Framework_CategoryFilterDefinitionType extends OnlineShop_Framework_AbstractFilterDefinitionType {

    public function getField() {
        if($this->getIncludeParentCategories()) {
            return "parentCategoryIds";
        } else {
            return "categoryIds";
        }
    }


    public function getIncludeParentCategories() {
        return false;
    }

}