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
   public abstract function getConditions();

   /**
   * @return Object_Fieldcollection
   */
   public abstract function getFilters();


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
