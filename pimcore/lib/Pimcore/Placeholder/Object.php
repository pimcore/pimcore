<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kisi
 * Date: 20.11.11
 * Time: 07:07
 * To change this template use File | Settings | File Templates.
 */
 
class Pimcore_Placeholder_Object extends Pimcore_Placeholder_Abstract{

    public function getTestValue(){
        return '<span class="testValue">Name of the Object</span>';
    }

    public function getReplacement(){
        $string = '';
        if($object = Object_Concrete::getById($this->getValue())){
            if(is_string($this->getPlaceholderConfig()->method) && method_exists($object,$this->getPlaceholderConfig()->method)){
                $string = $object->{$this->getPlaceholderConfig()->method}($this->getLocale());
            }
        }
        return $string;
    }
}