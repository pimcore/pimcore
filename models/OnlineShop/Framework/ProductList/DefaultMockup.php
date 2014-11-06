<?php

class OnlineShop_Framework_ProductList_DefaultMockup {

    protected $id;
    protected $params;

    public function __construct($id, $params, $relations) {
        $this->id = $id;
        $this->params = $params;

        $this->relations = array();
        if($relations) {
            foreach($relations as $relation) {
                $this->relations[$relation['fieldname']][] = array("id" => $relation['dest'], "type" => $relation['type']);
            }
        }

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }



    public function getRelationAttribute($attributeName) {

        $relationObjectArray = array();
        if($this->relations[$attributeName]) {
            foreach($this->relations[$attributeName] as $relation) {
                $relationObject = Element_Service::getElementById($relation['type'], $relation['id']);
                if($relationObject) {
                    $relationObjectArray[] = $relationObject;
                }
            }
        }

        if(count($relationObjectArray) == 1) {
            return $relationObjectArray[0];
        } else if(count($relationObjectArray) > 1) {
            return $relationObjectArray;
        } else {
            return null;
        }

    }


    public function __call($method, $args) {

        if(substr($method, 0, 3) == "get") {
            $attributeName = lcfirst(substr($method, 3));
            if(array_key_exists($attributeName, $this->params)) {
                return $this->params[$attributeName];
            }


            if(array_key_exists($attributeName, $this->relations)) {
                $relation = $this->getRelationAttribute($attributeName);
                if($relation) {
                    return $relation;
                }
            }

        }

        Logger::warn("Method $method not in Mockup implemented, delegating to object with id {$this->id}.");

        $object = Object_Abstract::getById($this->id);
        if($object) {
            return call_user_func_array(array($object, $method), $args);
        } else {
            throw new Exception("Object with {$this->id} not found.");
        }
    }







}