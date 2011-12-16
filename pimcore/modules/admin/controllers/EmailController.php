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

            #$params = $emailLog->getParams();
            #echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body{background-color:#fff;}</style></head><body><pre>'.Zend_Json::prettyPrint($params).'</pre></body></html>';
        }elseif($this->_getParam('type') == 'json'){
            if($this->_getParam('getData')){
         /*       $jsonData = Zend_Json::decode($emailLog->getParams());

                $preparedData = array();

                if(is_array($jsonData)){
                    foreach($jsonData as $key => $value){
                        $class = new StdClass();
                        $class->property = $key;
                        $class->data = $value;
                        $class->iconCls = 'task-folder';
                        $class->expanded = false;
                        $class->children = array();
                        $preparedData[] = $class;
                    }
                }
                #p_r($jsonData);
*/
$preparedData = <<<'xx'
[{"property":"object_id","children":[],"iconCls":"task-folder","data":"eins"},{"property":"products","children":[{"property":0,"children":[],"iconCls":"task-folder","data":"zwei"}],"iconCls":"task-folder","data":"drei"},{"property":"singleProdct","children":[],"iconCls":"task-folder","data":"zwei"},{"property":"localized","children":[{"property":0,"children":[],"iconCls":"task-folder","data":"zwei"}],"iconCls":"task-folder","data":"drei"},{"property":"fash","children":[{"property":0,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":1,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":2,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":3,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":4,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":5,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":6,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":7,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":8,"children":[],"iconCls":"task-folder","data":"zwei"}],"iconCls":"task-folder","data":"drei"},{"property":"asset","children":[{"property":0,"children":[],"iconCls":"task-folder","data":"zwei"},{"property":"hallo","children":[],"iconCls":"task-folder","data":"zwei"}],"iconCls":"task-folder","data":"drei"},{"property":"testarray","children":[{"property":"essen","children":[{"property":0,"children":[],"iconCls":"task-folder","data":"eins"},{"property":1,"children":[],"iconCls":"task-folder","data":"eins"}],"iconCls":"task-folder","data":"drei"},{"property":"getraenke","children":[{"property":0,"children":[],"iconCls":"task-folder","data":"eins"},{"property":1,"children":[],"iconCls":"task-folder","data":"eins"},{"property":2,"children":[],"iconCls":"task-folder","data":"eins"}],"iconCls":"task-folder","data":"drei"}],"iconCls":"task-folder","data":"drei"},{"property":"documents","children":[{"property":"one","children":[],"iconCls":"task-folder","data":"zwei"},{"property":"two","children":[],"iconCls":"task-folder","data":"zwei"}],"iconCls":"task-folder","data":"drei"}]
xx;




                $this->_helper->json(Zend_Json::decode($preparedData));


            }

        }
        else{
            die('No Type specified');
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
