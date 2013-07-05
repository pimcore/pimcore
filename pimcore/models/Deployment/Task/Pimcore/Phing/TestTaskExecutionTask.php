<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 10.05.13
 * Time: 08:15
 */
class Deployment_Task_Pimcore_Phing_TestTaskExecutionTask extends Deployment_Task_Pimcore_Phing_AbstractTask {

    public function main(){
        $this->log($this->getParam('message'),Project::MSG_INFO);
    }
}