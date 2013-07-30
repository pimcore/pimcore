<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 22.04.13
 * Time: 23:27
 */
Class Deployment_Instance_Wrapper {

    const ENV_DEV = 'development';
    const ENV_STAGING = 'staging';
    const ENV_LIVE = 'live';


    protected $identifier;
    protected $domain;
    protected $groups;
    protected $deployable;
    protected $webserviceApiKey;
    protected $webserviceEndpointRest;
    protected $requiredModules = array('Tool_UUID_Module','Deployment_Module');

    protected $concreteDeploymentInstance;

    public static function getEnvironmentTypes(){
        $environments = array();
        $reflection = new ReflectionClass(__CLASS__);

        foreach($reflection->getConstants() as $constantName => $constantValue){
            if(substr($constantName,0,4) == 'ENV_'){
                $environments[$constantName] = $constantValue;
            }
        }

        return $environments;
    }

    public function setFieldMapping($fieldMapping = array()){
        $this->fieldMapping = $fieldMapping;
        return $this;
    }

    protected function applyFieldMapping(){
        $fieldMapping = Deployment_Factory::getInstance()->getInstanceAdapter()->getFieldMapping();
        $concreteDeploymentInstance = $this->getConcreteDeploymentInstance();

        foreach($concreteDeploymentInstance as $key => $value){
            $setter = "set" . ucfirst($key);
            $getter = "get" . ucfirst($key);

            if(is_object($concreteDeploymentInstance) && method_exists($concreteDeploymentInstance,$getter)){ //to get inherited values
                $value = $concreteDeploymentInstance->$getter();
            }
            if($fieldMapping[$key]){ //apply field mapping
                $value = $concreteDeploymentInstance[$fieldMapping[$key]];
            }
            if(method_exists($this,$setter)){
                $this->$setter($value);
            }
        }

        if(is_object($concreteDeploymentInstance)){ //e.g for object adapter

            foreach((array)$fieldMapping as $wrapperField => $sourceField){
                $wrapperGetter = "get" . ucfirst($wrapperField);
                $wrapperSetter = "set" . ucfirst($wrapperField);

                if(method_exists($this,$wrapperSetter) && method_exists($this,$wrapperGetter)){
                    $sourceGetter = "get" . ucfirst($sourceField);
                    //if getter exists use the getter
                    if(is_object($concreteDeploymentInstance) && method_exists($concreteDeploymentInstance,$sourceGetter)){
                        $this->$wrapperSetter($concreteDeploymentInstance->$sourceGetter());
                    }
                    //if no getter exists use the public property
                    elseif(is_object($concreteDeploymentInstance) && !method_exists($concreteDeploymentInstance,$sourceGetter)){
                        $this->$wrapperSetter($concreteDeploymentInstance->$sourceField);
                        //use array key
                    }elseif(is_array($concreteDeploymentInstance)){
                        $this->$wrapperSetter($concreteDeploymentInstance[$sourceField]);
                    }
                }
            }
        }
        return $this;
    }

    protected function setSystemData(){
        $concreteDeploymentInstance = $this->getConcreteDeploymentInstance();
        $apiKey = Deployment_Helper_General::getValueByItemType('getWebserviceApiKey',$concreteDeploymentInstance);

        if($apiKey){
            try{
                $this->setWebserviceEndpointRest('http://' . Deployment_Helper_General::getValueByItemType('getDomain',$concreteDeploymentInstance) . '/webservice/rest/');
                $this->setWebserviceApiKey($apiKey);
                if($this->checkWebserviceRest()){
                    $this->setDeployable(1);
                }else{
                    $this->setDeployable(0);
                }
            }catch (Exception $e){
                Logger::warn("REST request failed. " . $this->getIdentifier() . ' Error:' . $e->getMessage());
                $this->setDeployable(0);
            }
        }else{
            Logger::warn("REST API key not available. " . $this->getIdentifier());
            $this->setDeployable(0);
        }

        if($this->instanceIsCurrentSystem()){
            $this->setDeployable(0);
        }
        return $this;
    }

    public function checkWebserviceRest(){

        if($this->instanceIsCurrentSystem()){
            return array('success' => true,'message' => 'Instance is current system.');
        }
        try{
            $restClient = $this->getRestClient();
            $serverInfo = $restClient->getServerInfo();

            $missingModules = array();
            if(empty($serverInfo->pimcore->modules)){
                $missingModules = $this->requiredModules;
            }else{
                foreach($this->requiredModules as $module){
                    if(!in_array($module,$serverInfo->pimcore->modules)){
                        $missingModules[] = $module;
                    }
                }
            }
            if(empty($missingModules)){
                return array('success' => true,'message' => 'Rest request successfully.');
            }else{
                $message = ' Missing Modules: ' . implode(', ',$missingModules) . ' | Rest request successfully.';
                if(in_array('Tool_UUID_Module',$missingModules)){
                    $message .= ' You have to set an instance identifier in "System Settings" -> "General" -> "Instance identifier" ';
                }
                return array('success' => false,'message' => $message);
            }
        }catch(Exception $e){
            return array('success' => false,'message' => 'Rest request failed -> ' .$e->getMessage());
        }
    }

    public function instanceIsCurrentSystem(){
        return ($this->getIdentifier() == Deployment_Helper_General::getInstanceIdentifier());
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setConcreteDeploymentInstance($concreteDeploymentInstance)
    {
        $this->concreteDeploymentInstance = $concreteDeploymentInstance;
        $this->applyFieldMapping();
        $this->setSystemData();
        return $this;
    }

    public function getConcreteDeploymentInstance()
    {
        return $this->concreteDeploymentInstance;
    }

    public function setDeployable($deployable)
    {
        $this->deployable = (int)$deployable;
        return $this;
    }

    public function getDeployable()
    {
        return $this->deployable;
    }

    /**
     * wrapper function for getDeployable()
     * @return bool
     */
    public function isDeployable(){
        return (bool)$this->getDeployable();
    }

    public function setWebserviceEndpointRest($webserviceEndpointRest)
    {
        $this->webserviceEndpointRest = $webserviceEndpointRest;
    }

    public function getWebserviceEndpointRest()
    {
        return $this->webserviceEndpointRest;
    }

    public function setWebserviceApiKey($webserviceApiKey)
    {
        $this->webserviceApiKey = $webserviceApiKey;
    }

    public function getWebserviceApiKey()
    {
        return $this->webserviceApiKey;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return Pimcore_Tool_RestClient
     */
    public function getRestClient(){
        $restClient = new Pimcore_Tool_RestClient();
        $restClient->setHost($this->getDomain());
        $restClient->setBaseUrl($this->getWebserviceEndpointRest());
        $restClient->setApiKey($this->getWebserviceApiKey());
        return $restClient;
    }

    /**
     * proxy to concrete deployment instance object if method doesn't exists
     *
     * @param $method
     * @param $args
     */
    public function __call($method,$args){
        $concreteDeploymentInstance = $this->getConcreteDeploymentInstance();
        return Deployment_Helper_General::getValueByItemType($method,$concreteDeploymentInstance);
    }

    public function setGroups($groups){
        $this->groups = $groups;
        return $this;
    }

    public function getGroups(){
        return $this->groups;
    }

}