<?php

/**
 * Abstract base class for filter definition type field collections for category filter
 */
abstract class OnlineShop_Framework_CategoryFilterDefinitionType extends OnlineShop_Framework_AbstractFilterDefinitionType {

    /**
     * @return string
     */
    public function getField() {
        if($this->getIncludeParentCategories()) {
            return "parentCategoryIds";
        } else {
            return "categoryIds";
        }
    }

    /**
     * @return bool
     */
    public function getIncludeParentCategories() {
        return false;
    }

}