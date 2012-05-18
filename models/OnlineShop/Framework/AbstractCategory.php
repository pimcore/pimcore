<?php

/**
 * Abstract base class for pimcore objects who should be used as product categories in the online shop framework
 */
class OnlineShop_Framework_AbstractCategory extends Object_Concrete {

    /**
     * @static
     * @param int $id
     * @return null|Object_Abstract
     */
    public static function getById($id) {
        $object = Object_Abstract::getById($id);

        if($object instanceof OnlineShop_Framework_AbstractCategory) {
            return $object;
        }
        return null;
    }

    /**
     * defines if product is visible in product index queries for parent categories of product category.
     * e.g.
     *   football
     *     - shoes
     *     - shirts
     *
     * all products if category shoes or shirts are visible in queries for category football
     *
     * @return bool
     */
    public function getOSProductsInParentCategoryVisible() {
        return true;
    }

}
