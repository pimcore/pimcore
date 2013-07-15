<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 22.04.13
 * Time: 23:39
 */

class Deployment_Instance_Adapter_Object  extends Deployment_Instance_Adapter_Abstract{

    protected $type = 'object';

    protected $instanceSettings;
    protected $instanceObjectClassName;
    protected $instanceObjectListClassName;

    protected $deploymentInstanceWrapperClassName; //default Deployment_Instance

    protected function init(){
        $key = 'instanceSettings' . ucfirst($this->getType());

        $instanceSettings = Deployment_Factory::getInstance()->getConfig()->$key;
        if(!$instanceSettings instanceof Zend_Config){
            throw new Exception("Couldn't find instanceSettings: '$key'!");
        }else{
            $this->instanceSettings = $instanceSettings;
        }

        if(!$instanceSettings->className){
            throw new Exception("No className provided.");
        }

        $className = "Object_" . ucfirst($instanceSettings->className);
        $className = Pimcore_Tool::getModelClassMapping($className);
        if(Pimcore_Tool::classExists($className)){
            $this->instanceObjectClassName = $className;
        }else{
            throw new Exception("Object class '$className' doesn't exists.");
        }

        $listClassName = "Object_" . ucfirst($instanceSettings->className).'_List';
        $listClassName = Pimcore_Tool::getModelClassMapping($listClassName);

        if(Pimcore_Tool::classExists($listClassName)){
            $this->instanceObjectListClassName = $listClassName;
        }else{
            throw new Exception("Object list class $listClassName doesn't exists.");
        }

        $this->deploymentInstanceWrapperClassName = Pimcore_Tool::getModelClassMapping('Deployment_Instance_Wrapper');
    }

    public function getInstanceSettings(){
        return $this->instanceSettings;
    }

    public function getInstanceObjectClassName(){
        return $this->instanceObjectClassName;
    }

    public function getInstanceObjectList(){
        return new $this->instanceObjectListClassName();
    }

    protected function getWrapperObject($concreteInstanceObject){
        if($concreteInstanceObject instanceof Object_Concrete){
            $concreteInstance = new $this->deploymentInstanceWrapperClassName();
            $wrappedObject = $concreteInstance->setConcreteDeploymentInstance($concreteInstanceObject);
            return $wrappedObject;
        }
    }

    public function getAllInstances(){
        $list = $this->getInstanceObjectList();
        $instances = array();
        foreach($list as $instanceObject){
            $instances[] = $this->getWrapperObject($instanceObject);
        }

        return $instances;
    }

    public function getInstancesByIdentifiers(array $identifiers){
        $list = $this->getInstanceObjectList();
        $fieldMapping = $this->getFieldMapping('identifier');
        $identifiers = wrapArrayElements($identifiers);
        $list->setCondition($fieldMapping["instanceIdentifier"].' IN(' . implode(',',$identifiers) .') ');
        $instances = array();
        foreach($list as $instanceObject){
            $instances[] = $this->getWrapperObject($instanceObject);
        }
        return $instances;
    }

    public function getInstancesByGroups(array $groups){
        $fieldMapping = $this->getFieldMapping('identifier');
        $dbField = $fieldMapping['instanceGroup'];
        $groups = wrapArrayElements($groups," $dbField LIKE '%,",",%' ");

        $list = $this->getInstanceObjectList();
        $list->setCondition(implode(' OR ', $groups));

        $instances = array();
        foreach($list as $instanceObject){
            $instances[] = $this->getWrapperObject($instanceObject);
        }
        return $instances;
    }

    public function getInstanceByIdentifier($identifier){
        $fieldMapping = $this->getFieldMapping('identifier');
        if($fieldMapping['instanceIdentifier'] == 'id'){
            $dbColumn = 'o_id';
        }else{
            $dbColumn = $fieldMapping['instanceIdentifier'];
        }
        $list = $this->getInstanceObjectList();
        $list->setCondition($dbColumn. ' = ?',array($identifier))->setLimit(1);
        $res = $list->load();
        if($res[0] instanceof Object_Concrete){
            return $this->getWrapperObject($res[0]);
        }
    }
}