<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 30.07.13
 * Time: 12:34
 */

class Deployment_Webservice_Service extends Webservice_Service {

    public function getDeploymentPackage($id){
        $package = Deployment_Package::getById($id);
        if($package instanceof Deployment_Package){
            return $package->getForWebserviceExport();
        }else{
            throw new Exception("Package with id $id not found.");
        }
    }


}