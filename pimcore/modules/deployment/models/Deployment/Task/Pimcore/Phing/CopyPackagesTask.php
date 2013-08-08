<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 08:15
 */
class Deployment_Task_Pimcore_Phing_CopyPackagesTask extends Deployment_Task_Pimcore_Phing_AbstractTask {

    protected $packageIds = array();


    //executed on source system
    public function main(){
        $deploymentAction = $this->getParam('deploymentAction');
        if($deploymentAction){
            $this->log("Executing deploymentAction '$deploymentAction'.",Project::MSG_INFO);
            if(!method_exists($this,$deploymentAction)){
                throw new BuildException("deploymentAction '$deploymentAction' not defined!");
            }
            $this->$deploymentAction();
        }else{
            $packageIds = explode_and_trim(',',$this->getParam('packageIds'));
            if(empty($packageIds)){
                $packageIds = array($this->project->getProperty('packageId'));
            }
            if(empty($packageIds)){
                throw new BuildException("No packageIds given.");
            }

            $instances = $this->getDeploymentInstances();
            if(count($instances) == 0){
                throw new BuildException("No deployment instances found!");
            }
            foreach($instances as $instance){
                $client = $instance->getRestClient();
                $serverInfo = $client->getServerInfo();
                $cmd = $serverInfo->system->phpCli .' ' . $serverInfo->pimcore->constants->PIMCORE_PATH .'/cli/deployment.php ';
                $cliParams = array('target' => 'pimcore.target.copyPackages',
                                   'deploymentAction' => 'downloadPackages',
                                   'sourceInstanceIdentifier' => Deployment_Helper_General::getInstanceIdentifier(),
                                   'packageIds' => $packageIds);

                $result = $client->deploymentExecuteTargetAction($cliParams);
                if($result->success){
                    $this->log("Remote command successfully (instance: " . $instance->getIdentifier().")",Project::MSG_INFO);
                    $this->log("Remote command: " . $result->data ,Project::MSG_DEBUG);
                }else{
                    $this->log("Remote command: " . $result->data ,Project::MSG_DEBUG);
                    throw new BuildException("Remote command failed (instance: " . $instance->getIdentifier().")");
                }
            }
        }
    }

    //executed on the remote system
    public function downloadPackages(){
        $sourceInstanceIdentifier = $this->getParam('sourceInstanceIdentifier');
        if(!$sourceInstanceIdentifier){
            throw new BuildException("No sourceInstanceIdentifier given.");
        }

        $packageIds = explode_and_trim(',',$this->getParam('packageIds'));
        if(empty($packageIds)){
            throw new BuildException("No packageIds given.");
        }

        $sourceInstance = $this->getDeploymentFactory()->getInstanceAdapter()->getInstanceByIdentifier($sourceInstanceIdentifier);
        if(!$sourceInstance instanceof Deployment_Instance_Wrapper){
            throw new BuildException('sourcInstance with identifier "' . $sourceInstanceIdentifier . "' not found!.");
        }

        $client = $sourceInstance->getRestClient();
        $packageData = array();


        foreach($packageIds as $packageId){
            $response = $client->getDeploymentPackageInformation($packageId);
            if($response->success){
                $packageData[$response->data->id] = $response->data;
            }else{
                throw new BuildException("Couldn't get deployment package information for Package with id '". $packageId ."'. Error:" . var_export($response->msg,true));
            }
        }

        $currentInstance = $this->getDeploymentFactory()->getInstanceAdapter()->getCurrentInstance();
        $fileTransfer = new Pimcore_File_Transfer($currentInstance->getTransportAdapter());

        foreach($packageData as $package){
            try{
                $sourceFile = $client->buildEndpointUrl('deployment-package-phar-data') . '&id=' . $package->id;
                $destinationFile = PIMCORE_DOCUMENT_ROOT . $package->pharFileWebsitePath;
                $fileTransfer->setSourceFile($sourceFile);
                $fileTransfer->setDestinationFile($destinationFile);
                $fileTransfer->send();
                $this->log("Downloaded Package with ID '{$package->id}' to path:\n $destinationFile");
            }catch(Exception $e){
                throw new BuildException("Couldn't download Package with id:" . $package->id .' Error: ' . $e->getMessage());
            }
        }
    }
}