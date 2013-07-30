<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 30.07.13
 * Time: 11:44
 */
require_once PIMCORE_DOCUMENT_ROOT . '/pimcore/modules/webservice/controllers/RestController.php';

class Deployment_RestController extends Webservice_RestController {

    /**
     * the webservice
     * @var
     */
    private $service;

    /**
     * The output encoder (e.g. json)
     * @var
     */
    private $encoder;

    public function init() {
        if ($this->getParam("condense")) {
            Object_Class_Data::setDropNullValues(true);
            Webservice_Data_Object::setDropNullValues(true);
        }

        $profile = $this->getParam("profiling");
        if ($profile) {
            $startTs = microtime(true);
        }

        parent::init();
        $this->disableViewAutoRender();
        $this->service = new Deployment_Webservice_Service();
        $this->encoder = new Webservice_JsonEncoder();

        if ($profile) {
            $this->timeConsumedInit = round(microtime(true) - $startTs,3)*1000;
        }
    }

    /**
     *  shows meta information from a deployment package
     */
    public function deploymentPackageInformationAction(){
        try{
            $result = $this->service->getDeploymentPackage($this->_getParam('id'));
            $this->encoder->encode(array("success" => true, "data" => $result));
        }catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => (string) $e));
        }
    }

    /**
     * download a deployment package
     */
    public function deploymentPackagePharDataAction(){
        try{
            $result = $this->service->getDeploymentPackage($this->_getParam('id'));
            $finfo = new finfo;
            $mimeType = $finfo->file($result['pharFile'], FILEINFO_MIME);
            header('Content-type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . Deployment_Task_Pimcore_Phing_AbstractPackageTask::PACKAGE_PHAR_ARCHIVE_FILE_NAME .'"');
            readfile($result['pharFile']);
        }catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => (string) $e));
        }
    }

    /**
     * executes a deployment target
     */
    public function deploymentExecuteTargetAction(){
        try{
            $cmd = Pimcore_Tool_Console::getPhpCli(). ' ' . PIMCORE_DOCUMENT_ROOT.'/pimcore/cli/deployment.php ';
            $queryParams = $this->getQueryParams();
            if(!$queryParams['target']){
                throw new Exception("No target specified.");
            }
            $cmd .= ' ' . Pimcore_Tool_Console::getOptionString($queryParams);
            Pimcore_Tool_Console::execInBackground($cmd,Deployment_Helper_General::getDefaultLogFile());
            $this->encoder->encode(array("success" => true, "data" => 'Command "' . $cmd .'" executed in background.'));
        }catch (Exception $e){
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => (string) $e));
        }
    }


}