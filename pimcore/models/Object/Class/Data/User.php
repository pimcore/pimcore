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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_User extends Object_Class_Data_Select {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "user";


    public function configureOptions() {

        $list = new User_List();
        $list->setOrder("asc");
        $list->setOrderKey("username");
        $users = $list->load();

        $options = array();
        if (is_array($users) and count($users) > 0) {
            foreach ($users as $user) {
                if ($user->getHasCredentials()) {
                    $value = $user->getUsername();
                    $first = $user->getFirstname();
                    $last = $user->getLastname();
                    if (!empty($first) or !empty($last)) {
                        $value .= " (" . $first . " " . $last . ")";
                    }
                    $options[] = array(
                        "value" => $user->getId(),
                        "key" => $value
                    );
                }
            }
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
            $user = User::getById($data);
            if(!$user instanceof User){
                throw new Exception("invalid user reference");
            }
        }
    }


    /**
     * Checks if data for this field is valid and removes broken dependencies
     *
     * @param Object_Abstract $object
     * @return bool
     */
    public function sanityCheck($object) {
        $key = $this->getName();
        $sane = true;
        try{
            $this->checkValidity($object->$key,true);
        } catch (Exception $e){
            logger::notice("Detected insane relation, removing reference to non existent user with username [".$object->$key."]");
            $object->$key = null;
            $sane = false;
        }
        return $sane;
    }


    public function __wakeup() {
        if(Pimcore::inAdmin()) {
            $this->configureOptions();
        }
    }


}
