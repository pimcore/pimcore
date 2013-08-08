<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 22.04.13
 * Time: 23:39
 */

class Deployment_Instance_Adapter_Xml  extends Deployment_Instance_Adapter_Abstract{

    /**
     * @var string Adapter type
     */
    protected $type = 'xml';


    protected $config = null;

    protected $instanceSettings;
    protected $instances = array();

    protected function init(){
        parent::init();
        $configFile = PIMCORE_CONFIGURATION_DIRECTORY.'/deployment/deploymentInstances.xml';
        if(!is_readable($configFile)){
            throw new Exception("Config file $configFile not readable or doesn't exist.");
        }else{
            $this->config = new Zend_Config_Xml($configFile);
        }
        $configArray = $this->config->toArray();
        if(isAssocArray($configArray['instances']['instance'])){
            $instances = array($configArray['instances']['instance']);
        }else{
            $instances = $configArray['instances']['instance'];
        }
        foreach($instances as $key => $instance){
            if($instance['groups']){
                $instances[$key]['groups'] = explode_and_trim(',',$instance['groups']);
            }
        }
        $this->instances = $instances;
    }

    protected function getWrapperObject($concreteInstanceXml){
        $concreteInstance = new $this->deploymentInstanceWrapperClassName();
        $wrappedObject = $concreteInstance->setConcreteDeploymentInstance($concreteInstanceXml);
        return $wrappedObject;
    }

    public function getAllInstances(){
        $instances = array();
        foreach($this->instances as $instance){
            $instances[] = $this->getWrapperObject($instance);
        }
        return $instances;
    }

    public function getInstancesByIdentifiers(array $identifiers){
        $instances = array();
        foreach($this->instances as $instance){
            if(in_array($instance['identifier'],$identifiers)){
                $instances[] = $this->getWrapperObject($instance);
            }
        }
        return $instances;
    }

    public function getInstancesByGroups(array $groups){
        $instances = array();
        foreach($this->instances as $instance){
            if(is_array($instance['groups']) && array_intersect($groups,$instance['groups'])){
                $instances[] = $this->getWrapperObject($instance);
            }
        }
        return $instances;
    }

    public function getConcreteInstances(){
        return $this->instances;
    }

    public function getInstanceByIdentifier($identifier){
        $instanceWithIdentifier = null;
        foreach($this->instances as $instance){
            if($identifier == $instance['identifier']){
                $instanceWithIdentifier = $instance;
                break;
            }
        }
        if($instanceWithIdentifier){
            return $this->getWrapperObject($instanceWithIdentifier);
        }
    }
}