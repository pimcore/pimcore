<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 08:11
 */

//set include path because otherwise we will get an error when we try to access data from e.g. Webservice
set_include_path(get_include_path() . PATH_SEPARATOR . PIMCORE_PATH . '/modules/deployment/lib/Deployment/Phing/classes');
require_once PIMCORE_PATH . '/modules/deployment/lib/Deployment/Phing/classes/phing/Task.php';

abstract class Deployment_Task_Pimcore_Phing_AbstractTask extends Task {
    protected $paramDefinitions = array();

    protected $deploymentFactory;

    protected $webserviceService;

    protected $pimcoreparams = array();

    protected $commandLineParams = array();

    protected static $mainDeploymentExecutionTarget = null;
    protected $currentDeploymentExecutionTarget = null;

    public function init(){
        parent::init();
        set_error_handler(array($this, 'errorHandler'));

        $this->setTaskParamDefinitions();
        $this->deploymentFactory = Deployment_Factory::getInstance();
        $this->webserviceService = new Webservice_Service();
        $this->commandLineParams = $this->deploymentFactory->getAdapter()->getCommandLineParams();
        $this->initDeploymentExecutionTarget();
    }

    protected function initDeploymentExecutionTarget(){

        if(!self::$mainDeploymentExecutionTarget instanceof Deployment_Target_Execution){

            $target = new Deployment_Target_Execution();

            $target->setName($this->getParam('target'));
            $target->setStatus(Deployment_Target_Execution::STATUS_START);
            $target->setCreationDate(time());

            self::$mainDeploymentExecutionTarget = $target->save();
        }else{
            if(!$this->currentDeploymentExecutionTarget){

                $target = new Deployment_Target_Execution();
                $target->setName($this->getOwningTarget()->getName());
                $target->setStatus(Deployment_Target_Execution::STATUS_START);
                $target->setCreationDate(time());
                $target->setParent(self::$mainDeploymentExecutionTarget);

                $this->currentDeploymentExecutionTarget = $target->save();
            }
        }

    }

    protected function getCurrentDeploymentExecutionTarget(){
        return $this->currentDeploymentExecutionTarget;
    }

    protected function getDeploymentFactory(){
        return $this->deploymentFactory;
    }

    protected function getWebserviceService(){
        return $this->webserviceService;
    }

    protected function getWebserviceEncoder(){
        $config = Deployment_Helper_General::getConfig()->toArray();
        $encoderClass = $config['webserviceEncoder'];
        return new $encoderClass();
    }

    /** Add a nested <pimcoreParam> tag. - filled by Phing */
    public function createpimcoreparam() {
        $param = new Deployment_Classes_Phing_Param();
        $this->pimcoreparams[] = $param; //holds a reference which is then filled by Phing
        return $param;
    }

    protected function setTaskParamDefinitions(){
        $allParamDefinitions = Deployment_Helper_General::getTaskParamDefinitions();
        if(isset($allParamDefinitions[$this->getTaskName()])){
            $this->paramDefinitions = $allParamDefinitions[$this->getTaskName()];
        }
    }

    /**
     * return a parameter - cli param overwrites pimcoreParams
     */
    protected function getParam($name){
        $pimcoreParam = $this->getPimcoreParam($name,false);
        $cliParam = $this->getCommandLinePram($name,false);
        if($cliParam != ''){
            return $this->getCommandLinePram($name);
        }else{
            return $this->getPimcoreParam($name);
        }
    }

    protected function getPimcoreParam($name, $validate = true){
        return $this->receiveParam($name,'pimcoreparams',$validate);
    }

    protected function getCommandLinePram($name, $validate = true){
        return $this->receiveParam($name,'commandLineParams', $validate);
    }

    protected function receiveParam($name,$source,$validate = true){
        $params = array();

        foreach($this->$source as $param){
            if($param->getName() == $name){
                $params[$name][] = $param;
            }
        }

        if($validate){
            if($config = $this->paramDefinitions[$name]){
                if($config['multipleDefinitions'] == 0 && count($params[$name]) > 1){
                    throw new BuildException("Parameter '$name' is declared multiple times but is defined as a unique param.");
                }

                if($config['required'] == 1 && is_null($params[$name])){
                    throw new BuildException("Missing required parameter '$name'");
                }
            }
        }

        if($config['multipleDefinitions']){
            $data = array();
            foreach((array)$params[$name] as $entry){
                $data[] = (string)$entry;
            }

            return $data;
        }else{
            return (string)$params[$name][0];
        }
    }

    /**
     * returns params- cli params overwrites pimcoreParams
     */
    protected function getParams(){
        $pimcoreParams = $this->getPimcoreParams();
        $commandLineParams = $this->getCommandLinePrams();
        $params = array_merge($pimcoreParams,$commandLineParams);
        return $params;
    }

    protected function getCommandLinePrams(){
        return $this->receiveParams('commandLineParams');
    }

    protected function getPimcoreParams(){
        return $this->receiveParams('pimcoreparams');
    }

    protected function receiveParams($source){
        $params = array();

        foreach($this->$source as $param){
            $name = $param->getName();
            if(!isset($params[$name])){
                $params[$name] = (string)$param;
            }else{
                $params[$name] = (array)$params[$name];
                $params[$name][] = (string)$param;
            }
        }

        return $params;
    }

    public function main(){

        $deploymentAction = $this->getParam('deploymentAction');
        if($deploymentAction){
            $this->log("Executing deploymentAction '$deploymentAction'.",Project::MSG_INFO);
            if(!method_exists($this,$deploymentAction)){
                throw new BuildException("deploymentAction '$deploymentAction' not defined!");
            }
            $this->$deploymentAction();
        }else{
            $this->log("No deploymentAction -> executing 'main' action.",Project::MSG_INFO);
        }
    }

    public function errorHandler($level, $message, $file, $line, $context) {
        //ignore E_NOTICE warnings when running whith --debug
        if($level != E_NOTICE){
            Phing::handlePhpError($level, $message, $file, $line);
        }
    }

    protected function getDeploymentInstances(){
        $deploymentFactory = $this->getDeploymentFactory();
        $instanceAdapter = $deploymentFactory->getInstanceAdapter();

        if($this->getParam('deploymentInstanceIds') && $this->getParam('deploymentGroups')){
            throw new BuildException("You have to use deploymentInstanceIds OR deploymentGroups.");
        }

        $instanceIdentifiers = explode_and_trim(',',$this->getParam('instanceIdentifiers'));
        if(!empty($instanceIdentifiers)){
            $instances = $instanceAdapter->getInstancesByIdentifiers($instanceIdentifiers);
            $this->log("Getting deploymentInstances by instanceIdentifiers'" . implode(',', $instanceIdentifiers)."'.",Project::MSG_DEBUG);
        }

        $deploymentGroups = explode_and_trim(',',$this->getParam('deploymentGroups'));
        if(!empty($deploymentGroups)){
            $instances = $instanceAdapter->getInstancesByGroups($deploymentGroups);
            $this->log("Getting deploymentInstances by deploymentGroups '" . implode(',', $deploymentGroups)."'.",Project::MSG_DEBUG);
        }

        return $instances;
    }

}

