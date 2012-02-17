<?php

abstract class OnlineShop_Framework_AbstractFilterDefinition extends Object_Concrete {

    public static function getById($id) {
        $object = Object_Abstract::getById($id);

        if($object instanceof OnlineShop_Framework_AbstractFilterDefinition) {
            return $object;
        }
        return null;
    }

    /**
     * @abstract
     * @return float
     */
    public abstract function getPageLimit();

   /**
   * @return string
   */
   public abstract function getOrderByAsc();

    /**
    * @return string
    */
    public abstract function getOrderByDesc();

   /**
   * @return Object_Fieldcollection
   */
   public abstract function getConditions ();

   /**
   * @return Object_Fieldcollection
   */
   public abstract function getFilters ();

}
