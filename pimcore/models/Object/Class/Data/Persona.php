<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_Persona extends Object_Class_Data_Select {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "persona";


    /**
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {

        if(!empty($data)) {
            try {
                $this->checkValidity($data, true);
            } catch (Exception $e) {
                $data = null;
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @param null $object
     */
    public function getDataForResource($data, $object = null) {
        if(!empty($data)) {
            try {
                $this->checkValidity($data, true);
            } catch (Exception $e) {
                $data = null;
            }
        }

        return $data;
    }


    /**
     *
     */
    public function configureOptions() {

        $list = new Tool_Targeting_Persona_List();
        $list->setOrder("asc");
        $list->setOrderKey("name");
        $personas = $list->load();

        $options = array();
        foreach ($personas as $persona) {
            $options[] = array(
                "value" => $persona->getId(),
                "key" => $persona->getName()
            );
        }

        $this->setOptions($options);
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new Exception("Empty mandatory field [ ".$this->getName()." ]");
        }
        
        if(!empty($data)){
            $persona = Tool_Targeting_Persona::getById($data);
            if(!$persona instanceof Tool_Targeting_Persona){
                throw new Exception("invalid persona reference");
            }
        }
    }

    public function __wakeup() {
        $options = $this->getOptions();
        if(Pimcore::inAdmin() || empty($options)) {
            $this->configureOptions();
        }
    }


}
