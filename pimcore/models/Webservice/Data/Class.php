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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Webservice_Data_Class extends Webservice_Data {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $userOwner;

    /**
     * @var int
     */
    public $userModification;

    /**
     * Name of the parent class if set
     *
     * @var string
     */
    public $parentClass;

    /**
     * @var boolean
     */
    public $allowInherit = false;

    /**
     * @var boolean
     */
    public $allowVariants = false;

    /**
     * @var boolean
     */
    public $showVariants = false;

    /**
     * @var array
     */
    public $fieldDefinitions;

    /**
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $previewUrl;


  

    public function map ($class) {

        $arr = $class->fieldDefinitions;
        $result = array();
        foreach ($arr as $item) {
            $result[] = $item;
        }
        $class->fieldDefinitions = $item;

        parent::map($class);
//        $fd = $class->getFieldDefinitions();
//
//        foreach ($fd as $field) {
//
//            $getter = "get".ucfirst($field->getName());
//
//            //only expose fields which have a get method
//            if(method_exists($class,$getter)){
//                $el = new Webservice_Data_Object_Element();
//                $el->name = $field->getName();
//                $el->type = $field->getFieldType();
//                $el->value = $field->getForWebserviceExport($class);
//                $this->elements[] = $el;
//            }
//
//        }

    }




    
}
