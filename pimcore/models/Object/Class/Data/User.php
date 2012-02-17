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

        $list = new User_List();
        $list->setOrder("asc");
        $list->setOrderKey("name");
        $users = $list->load();

        $options = array();
        if (is_array($users) and count($users) > 0) {
            foreach ($users as $user) {
                if($user instanceof User) {
                    $value = $user->getName();
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

    public function __wakeup() {
        if(Pimcore::inAdmin()) {
            $this->configureOptions();
        }
    }


}
