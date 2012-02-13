<?php

abstract class OnlineShop_Framework_AbstractFilterDefinitionType extends Object_Fieldcollection_Data_Abstract {

    /**
    * @return string
    */
    public abstract function getLabel ();

    /**
    * @return string
    */
    public abstract function getField ();

}
