<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 30.07.13
 * Time: 13:19
 */

class Deployment_PackagesController extends Pimcore_Controller_Action_Admin {

    public function init(){
        parent::init();
        if(!$this->getUser()->isAllowed('deployment')){
            throw new Exception("Current action is not permitted");
        }
    }
    public function listAction(){
        $list = new Deployment_Package_List();
        $list->setOrder('DESC');
        $list->setOrderKey('id');
        $list->setLimit($this->_getParam("limit",20));
        $list->setOffset($this->_getParam("start",0));

        $objects = $list->load();
        $this->_helper->json(array('data' => $objects,
                                  'total' => $list->getTotalCount()));
    }

    public function deleteAction(){
        if($this->getUser()->isAllowed('deployment')){

            $package = Deployment_Package::getById($this->_getParam('id'),array('limit' => 1,'unpublished' => true));
            if($package instanceof Deployment_Package){
                $package->delete();
                $this->_helper->json(array('success' => true));
            }else{
                $this->_helper->json(array('success' => false, 'message' => "Package with id " . $this->_getParam('id') . " doesn't exist."));
            }
        }else{
            throw new Exception("Deletion of deployment Package is not permitted.");
        }
    }

    public function downloadAction(){
        $package = Deployment_Package::getById($this->_getParam('id'),array('limit' => 1,'unpublished' => true));
        if($package instanceof Deployment_Package){
            $this->disableViewAutoRender();
            $packageData = $package->getForWebserviceExport();
            $finfo = new finfo;
            $mimeType = $finfo->file($packageData['pharFile'], FILEINFO_MIME);
            header('Content-type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . Deployment_Task_Pimcore_Phing_AbstractPackageTask::PACKAGE_PHAR_ARCHIVE_FILE_NAME .'"');
            readfile($packageData['pharFile']);
        }else{
            $this->_helper->json(array('success' => false, 'message' => "Package with id " . $this->_getParam('id') . " doesn't exist."));
        }
    }
}