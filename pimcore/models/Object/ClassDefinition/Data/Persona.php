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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Tool;

class Persona extends Model\Object\ClassDefinition\Data\Select {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "persona";


    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {

        if(!empty($data)) {
            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data = null;
            }
        }

        return $data;
    }

    /**
     * @param string $data
     * @param null $object
     * @return null|string
     */
    public function getDataForResource($data, $object = null) {
        if(!empty($data)) {
            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data = null;
            }
        }

        return $data;
    }


    /**
     *
     */
    public function configureOptions() {

        $list = new Tool\Targeting\Persona\Listing();
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
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }
        
        if(!empty($data)){
            $persona = Tool\Targeting\Persona::getById($data);
            if(!$persona instanceof Tool\Targeting\Persona){
                throw new \Exception("invalid persona reference");
            }
        }
    }

    /**
     *
     */
    public function __wakeup() {
        $options = $this->getOptions();
        if(\Pimcore::inAdmin() || empty($options)) {
            $this->configureOptions();
        }
    }
}
