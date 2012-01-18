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

class Admin_EmailController extends Pimcore_Controller_Action_Admin_Document
{

    public function getDataByIdAction()
    {

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
        $email->idPath = Pimcore_Tool::getIdPathForElement($email);
        $email->userPermissions = $email->getUserPermissions();

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

    public function saveAction()
    {

        if ($this->_getParam("id")) {
            $page = Document_Email::getById($this->_getParam("id"));

            $page = $this->getLatestVersion($page);
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


                try {
                    $page->save();
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    Logger::err($e);
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }

            }
            else {
                if ($page->isAllowed("save")) {
                    $this->setValuesToDocument($page);


                    try {
                        $page->saveVersion();
                        $this->_helper->json(array("success" => true));
                    } catch (Exception $e) {
                        Logger::err($e);
                        $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                    }

                }
            }
        }
        $this->_helper->json(false);


    }

    protected function setValuesToDocument(Document $page)
    {

        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
        $this->addSchedulerToDocument($page);
    }

    /**
     * returns the a list of log files for the data grid
     */
    public function emailLogsAction()
    {
        $list = new Document_Email_Log_List();
        if ($this->_getParam('documentId')) {
            $list->setCondition('documentId = ' . (int)$this->_getParam('documentId'));
        }
        $list->setLimit($this->_getParam("limit"));
        $list->setOffset($this->_getParam("start"));
        $list->setOrderKey("sentDate");
        $list->setOrder("DESC");

        $data = $list->load();
        $jsonData = array();

        if (is_array($data)) {
            foreach ($data as $entry) {
                $tmp = (array)get_object_vars($entry);
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

    /**
     * Shows the email logs and returns the Json object for the dynamic params
     */
    public function showEmailLogAction()
    {
        $type = $this->_getParam('type');
        $emailLog = Document_Email_Log::getById($this->_getParam('id'));

        if ($this->_getParam('type') == 'text') {
            $this->disableViewAutoRender();
            echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body{background-color:#fff;}</style></head><body><pre>' . $emailLog->getTextLog() . '</pre></body></html>';
        } elseif ($this->_getParam('type') == 'html') {
            $this->disableViewAutoRender();
            echo $emailLog->getHtmlLog();
        } elseif ($this->_getParam('type') == 'params') {
            $this->disableViewAutoRender();
            try {
                $params = Zend_Json::decode($emailLog->getParams());
            } catch (Exception $e) {
                Logger::warning("Could not decode JSON param string");
                $params = array();
            }
            foreach ($params as &$entry) {
                $this->enhanceLoggingData($entry);
            }
            $this->_helper->json($params);
        }
        else {
            die('No Type specified');
        }
    }

    /**
     * Helper to build the correct Json array for the treeGrid
     *
     * @param array $data
     * @param null|$data $fullEntry
     */
    protected function enhanceLoggingData(&$data, &$fullEntry = null)
    {

        if ($data['objectId']) {
            $class = $data['objectClass'];
            $obj = $class::getById($data['objectId']);
            if (is_null($obj)) {
                $data['objectPath'] = '';
            } else {
                $data['objectPath'] = $obj->getFullPath();
            }
            $tmp = explode('_', $data['objectClass']);
            if (in_array($tmp[0], array('Object', 'Document', 'Asset'))) {
                $data['objectClassBase'] = $tmp[0];
                $data['objectClassSubType'] = $tmp[1];
            }
        }

        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->enhanceLoggingData($value, $data);
            }
        }
        if ($data['children']) {
            foreach ($data['children'] as $key => $entry) {
                if (is_string($key)) { //key must be integers
                    unset($data['children'][$key]);
                }
            }
            $data['iconCls'] = 'task-folder';
            $data['data'] = array('type' => 'simple', 'value' => 'Children (' . count($data['children']) . ')');
        } else {
            //setting the icon class
            if (!$data['iconCls']) {
                if ($data['objectClassBase'] == 'Object') {
                    $fullEntry['iconCls'] = 'pimcore_icon_object';
                } elseif ($data['objectClassBase'] == 'Asset') {
                    switch ($data['objectClass']) {
                        case 'Asset_Image':
                            $fullEntry['iconCls'] = 'pimcore_icon_image';
                            break;
                        case 'Asset_Video':
                            $fullEntry['iconCls'] = 'pimcore_icon_wmv';
                            break;
                        case 'Asset_Text':
                            $fullEntry['iconCls'] = 'pimcore_icon_txt';
                            break;
                        case 'Asset_Document':
                            $fullEntry['iconCls'] = 'pimcore_icon_pdf';
                            break;
                        default :
                            $fullEntry['iconCls'] = 'pimcore_icon_asset';
                    }
                } elseif (strpos($data['objectClass'], 'Document') === 0) {
                    $fullEntry['iconCls'] = 'pimcore_icon_' . strtolower($data['objectClassSubType']);
                } else {
                    $data['iconCls'] = 'task';
                }
            }

            $data['leaf'] = true;
        }
    }


    /**
     * Deletes a single log entry
     */
    public function deleteEmailLogAction()
    {
        $success = false;
        $emailLog = Document_Email_Log::getById($this->_getParam('id'));
        if ($emailLog instanceof Document_Email_Log) {
            $emailLog->delete();
            $success = true;
        }
        $this->_helper->json(array(
            "success" => $success,
        ));
    }

    /**
     * Resends the email to the recipients
     */
    public function resendEmailAction(){
        $success = false;
        $emailLog = Document_Email_Log::getById($this->_getParam('id'));

        if($emailLog instanceof Document_Email_Log){
            $mail = new Pimcore_Mail();
            $mail->preventDebugInformationAppending();

            if($html = $emailLog->getHtmlLog()){
                $mail->setBodyHtml($html);
            }

            if($text = $emailLog->getTextLog()){
                $mail->setBodyText($text);
            }

            $mail->setFrom($emailLog->getFrom());

            foreach($emailLog->getToAsArray() as $entry){
                $mail->addTo($entry['email'],$entry['name']);
            }

            foreach($emailLog->getCcAsArray() as $entry){
                $mail->addCc($entry['email'],$entry['name']);
            }

            foreach($emailLog->getBccAsArray() as $entry){
                $mail->addBcc($entry['email']);
            }
            $mail->setSubject($emailLog->getSubject());
            $mail->send();
            $success = true;
        }

        $this->_helper->json(array(
            "success" => $success,
        ));
    }

}
