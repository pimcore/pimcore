<?php

/**
 * Abstract base class for filter definition pimcore objects
 */
abstract class OnlineShop_Framework_AbstractFilterDefinition extends Object_Concrete {

    /**
     * @static
     * @param int $id
     * @return null|Object_Abstract
     */
    public static function getById($id) {
        $object = Object_Abstract::getById($id);

        if($object instanceof OnlineShop_Framework_AbstractFilterDefinition) {
            return $object;
        }
        return null;
    }

    /**
     * returns page limit for product list
     *
     * @abstract
     * @return float
     */
    public abstract function getPageLimit();

   /**
     * returns list of available fields for sorting ascending
     *
     * @abstract
     * @return string
     */
   public abstract function getOrderByAsc();

    /**
     * returns list of available fields for sorting descending
     *
     * @abstract
     * @return string
    */
    public abstract function getOrderByDesc();

   /**
    * return array of field collections for preconditions
    *
    * @abstract
    * @return Object_Fieldcollection
    */
   public abstract function getConditions();

    /**
     * return array of field collections for filters
     *
     * @abstract
     * @return Object_Fieldcollection
     */
   public abstract function getFilters();


    /**
     * enables inheritance for field collections, if xxxInheritance field is available and set to string 'true'
     *
     * @param string $key
     * @return mixed|Object_Fieldcollection
     */
    public function preGetValue($key) {

        if ($this->getClass()->getAllowInherit()
            && Object_Abstract::doGetInheritedValues()
            && $this->getClass()->getFieldDefinition($key) instanceof Object_Class_Data_Fieldcollections
        ) {

            $checkInheritanceKey = $key . "Inheritance";
            if ($this->{
                'get' . $checkInheritanceKey
                }() == "true"
            ) {
                $parentValue = $this->getValueFromParent($key);
                if (!$this->$key) {
                    return $parentValue;
                } else {
                    $value = new Object_Fieldcollection($this->$key->getItems());
                    if (!empty($parentValue)) {
                        foreach ($parentValue as $entry) {
                            $value->add($entry);
                        }
                    }
                    return $value;
                }
            }
        }

        return parent::preGetValue($key);
    }

}
