<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 22.04.13
 * Time: 23:36
 */

Abstract class Deployment_Instance_Adapter_Abstract {

    protected static $instance;

    public static function getInstance(){
        if(!static::$instance){
            $config = Deployment_Factory::getInstance()->getConfig();
            if(Pimcore_Tool::classExists($config->instanceAdapter)){
                $instanceAdapterClass = $config->instanceAdapter;
                $instanceAdapter = new $instanceAdapterClass;
                if($instanceAdapter instanceof self){
                    static::$instance = $instanceAdapter;
                }else{
                    throw new Exception("The instanceAdapter has to be an instance of Deployment_Instance_Adapter_Abstract");
                }
            }else{
                throw new Exception("Could't find deploymentInstance adapter class: {$config->instanceAdapter}");
            }
        }
        return static::$instance;
    }

    public function getType(){
        return $this->type;
    }

    protected function __construct(){
        static::init();
    }

    protected function init(){
    }

    public function getInstanceSettings(){}

    public function getFieldMapping(){
        $instanceSettings = $this->getInstanceSettings();
        if($instanceSettings instanceof Zend_Config && $instanceSettings->fieldMapping instanceof Zend_Config){
            $fieldMapping = $instanceSettings->fieldMapping->toArray();
        }elseif(is_array($instanceSettings) && $instanceSettings['fieldMapping']){
            $fieldMapping = (array)$instanceSettings['fieldMapping'];
        }
        return $fieldMapping;
    }

    public function getCurrentInstance(){
        $instanceIdentifier = Deployment_Helper_General::getInstanceIdentifier();
        if(!$instanceIdentifier){
            throw new BuildException("No instance identifier set for this system!");
        }
        return $this->getInstanceByIdentifier($instanceIdentifier);
    }



    abstract function getAllInstances();
    abstract function getInstanceByIdentifier($identifier);
}