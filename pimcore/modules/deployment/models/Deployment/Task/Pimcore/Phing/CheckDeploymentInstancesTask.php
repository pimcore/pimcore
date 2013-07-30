<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 08:15
 */
class Deployment_Task_Pimcore_Phing_CheckDeploymentInstancesTask extends Deployment_Task_Pimcore_Phing_AbstractTask {

    protected $instanceIdentifier;

    public function setInstanceIdentifier($value){
        $this->instanceIdentifier = $value;
    }

    public function getInstanceIdentifier(){
        return $this->instanceIdentifier;
    }

    protected function getDeploymentInstances(){
        $deploymentFactory = $this->getDeploymentFactory();
        $instanceAdapter = $deploymentFactory->getInstanceAdapter();

        $instances = $instanceAdapter->getAllInstances();
        return $instances;
    }

    public function main(){

        if($identifier = $this->getInstanceIdentifier()){
            $instance = $this->getDeploymentFactory()->getInstanceAdapter()->getInstanceByIdentifier($this->getInstanceIdentifier());
            if(!$instance instanceof Deployment_Instance_Wrapper){
                throw new BuildException("Couldn't find instance with instanceIdentifier '" . $this->getInstanceIdentifier() ."'");
            }else{
                $instances[] = $instance;
            }
        }else{
            $instances = $this->getDeploymentInstances();
        }

        $invalidInstances = array();
        if(!empty($instances)){
            foreach($instances as $instance){
                if($instance->instanceIsCurrentSystem()){
                    $this->log("Skipping check because the instance with the identifier '". $instance->getIdentifier() ."' is the current system.",Project::MSG_INFO);
                }else{
                    $restCheck = $instance->checkWebserviceRest();
                    if($restCheck['success']){
                        $this->log("Instance with identifier '".$instance->getIdentifier()."' is valid." );
                    }else{
                        $invalidInstances[] = $instance->getIdentifier();
                        $this->log("Invalid deployment deployment instance with Identifier '".$instance->getIdentifier()."'. ErrorMessage:" . $restCheck['message'], Project::MSG_ERR);
                    }
                }
            }
        }else{
            throw new BuildException("No deployment instances found!");
        }

        if(!empty($invalidInstances)){
            throw new BuildException("Invalid deployment instances (" . implode(', ',$invalidInstances).')');
        }
    }

}