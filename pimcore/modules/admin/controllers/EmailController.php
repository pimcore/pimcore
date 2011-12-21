<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_EmailController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->_getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->_getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->_getParam("id"), "document");

        $email = Document_Email::getById($this->_getParam("id"));
        $email = $this->getLatestVersion($email);
        $email->getVersions();
        //$page->getPermissions();
        #$email->getScheduledTasks();
        $email->getPermissionsForUser($this->getUser());
        $email->idPath = Pimcore_Tool::getIdPathForElement($email);

        // unset useless data
        $email->setElements(null);
        $email->childs = null;

        // cleanup properties
        $this->minimizeProperties($email);
 
        if ($email->isAllowed("view")) {
            $this->_helper->json($email);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {

        if ($this->_getParam("id")) {
            $page = Document_Email::getById($this->_getParam("id"));
            
            $page = $this->getLatestVersion($page);
            
            $page->getPermissionsForUser($this->getUser());
            $page->setUserModification($this->getUser()->getId());

            // save to session
            $key = "document_" . $this->_getParam("id");
            $session = new Zend_Session_Namespace("pimcore_documents");
            $session->$key = $page;

            if ($this->_getParam("task") == "unpublish") {
                $page->setPublished(false);
            }
            if ($this->_getParam("task") == "publish") {
                $page->setPublished(true);
            }
            // only save when publish or unpublish
            if (($this->_getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->_getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                $this->setValuesToDocument($page);


                try{
                    $page->save();
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    Logger::err($e);
                    $this->_helper->json(array("success" => false,"message"=>$e->getMessage()));
                }

            }
            else {
                if ($page->isAllowed("save")) {
                    $this->setValuesToDocument($page);


                    try{
                    $page->saveVersion();
                        $this->_helper->json(array("success" => true));
                    } catch (Exception $e) {
                        Logger::err($e);
                        $this->_helper->json(array("success" => false,"message"=>$e->getMessage()));
                    }

                }
            }
        }
        $this->_helper->json(false);


    }

    protected function setValuesToDocument(Document $page) {

        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
        $this->addSchedulerToDocument($page);
    }

     public function emailLogsAction(){ //ckogler
        $list = new Document_Email_Log_List();
        if($this->_getParam('documentId')){
            $list->setCondition('documentId = '. (int) $this->_getParam('documentId'));
        }
        $list->setLimit($this->_getParam("limit"));
        $list->setOffset($this->_getParam("start"));
        $list->setOrderKey("sentDate");
        $list->setOrder("DESC");

        $data = $list->load();
        $jsonData = array();

        if(is_array($data)){
            foreach($data as $entry){
                $tmp = (array) get_object_vars($entry);
                unset($tmp['bodyHtml']);
                unset($tmp['bodyText']);
                $jsonData[] = $tmp;
            }
        }

        $this->_helper->json(array(
            "data" => $jsonData,
            "success" => true,
            "total" => $list->getTotalCount()
        ));
    }

    public function showEmailLogAction(){
        $type = $this->_getParam('type');
        $emailLog = Document_Email_Log::getById($this->_getParam('id'));

        if($this->_getParam('type') == 'text'){
            $this->disableViewAutoRender();
            echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body{background-color:#fff;}</style></head><body><pre>'.$emailLog->getTextLog().'</pre></body></html>';
        }elseif($this->_getParam('type') == 'html'){
            $this->disableViewAutoRender();
            echo $emailLog->getHtmlLog();
        }elseif($this->_getParam('type') == 'params'){
            $this->disableViewAutoRender();
            try{
                $params = Zend_Json::decode($emailLog->getParams());
            }catch(Exception $e){
                Logger::warning("Could not decode JSON param string");
                $params = array();
            }
            foreach($params as &$entry){
                $this->enhanceLoggingData($entry);
            }
            $this->_helper->json($params);
        }
        else{
            die('No Type specified');
        }
    }

    protected function enhanceLoggingData(&$data){
        if($data['objectId']){
            $class = $data['objectClass'];
            $obj = $class::getById($data['objectId']);
            if(is_null($obj)){
                $data['objectPath'] = '';
            }else{
                $data['objectPath'] = $obj->getFullPath();
            }
            $tmp = explode('_',$data['objectClass']);
            if(in_array($tmp[0],array('Object','Document','Asset'))){
                $data['objectClassBase'] = $tmp[0];
                $data['objectClassSubType'] = $tmp[1];
            }

           # switch($data['objectClassBase']){
           #     case 'Object' : $data['iconCls'] = 'aaa'; break;
           # }
        }
        foreach($data as $key => &$value){
            if(is_array($value)){
                    $this->enhanceLoggingData($value);
            }
        }
        if($data['children']){
            foreach($data['children'] as $key =>  $entry){
                if(is_string($key)){ //key must be integers
                    unset($data['children'][$key]);
                }
            }
           $data['iconCls'] = 'task-folder';
           $data['data'] = array('type' => 'simple', 'value' => 'Children (' . count($data['children']). ')');
        }else{
            if(!$data['iconCls']){
                $data['iconCls'] = 'task';
            }


            $data['leaf'] = true;
        }
    }

    public function deleteEmailLogAction(){
        $success = false;
        $emailLog = Document_Email_Log::getById($this->_getParam('id'));
        if($emailLog instanceof Document_Email_Log){
            $emailLog->delete();
            $success = true;
        }
        $this->_helper->json(array(
            "success" => $success,
        ));
    }

}
