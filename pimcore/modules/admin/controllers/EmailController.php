<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Mail;
use Pimcore\Model\Element;
use Pimcore\Model\Document;
use Pimcore\Model\Tool;
use Pimcore\Logger;

class Admin_EmailController extends \Pimcore\Controller\Action\Admin\Document
{
    public function getDataByIdAction()
    {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json([
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "document")
            ]);
        }
        Element\Editlock::lock($this->getParam("id"), "document");

        $email = Document\Email::getById($this->getParam("id"));
        $email = clone $email;
        $email = $this->getLatestVersion($email);

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, 0, 1));
        $email->idPath = Element\Service::getIdPath($email);
        $email->userPermissions = $email->getUserPermissions();
        $email->setLocked($email->isLocked());
        $email->setParent(null);

        // unset useless data
        $email->setElements(null);
        $email->childs = null;

        $this->addTranslationsData($email);
        $this->minimizeProperties($email);


        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($email));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $email,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($email->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
        try {
            if ($this->getParam("id")) {
                $page = Document\Email::getById($this->getParam("id"));

                $page = $this->getLatestVersion($page);
                $page->setUserModification($this->getUser()->getId());

                if ($this->getParam("task") == "unpublish") {
                    $page->setPublished(false);
                }
                if ($this->getParam("task") == "publish") {
                    $page->setPublished(true);
                }
                // only save when publish or unpublish
                if (($this->getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                    $this->setValuesToDocument($page);


                    try {
                        $page->save();
                        $this->saveToSession($page);
                        $this->_helper->json(["success" => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);
                        $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed("save")) {
                        $this->setValuesToDocument($page);


                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);
                            $this->_helper->json(["success" => true]);
                        } catch (\Exception $e) {
                            if ($e instanceof Element\ValidationException) {
                                throw $e;
                            }

                            Logger::err($e);
                            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                $this->_helper->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }

        $this->_helper->json(false);
    }

    /**
     * @param Document $page
     */
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
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $list = new Tool\Email\Log\Listing();
        if ($this->getParam('documentId')) {
            $list->setCondition('documentId = ' . (int)$this->getParam('documentId'));
        }
        $list->setLimit($this->getParam("limit"));
        $list->setOffset($this->getParam("start"));
        $list->setOrderKey("sentDate");

        if ($this->getParam('filter')) {
            if ($this->getParam("filter")) {
                $filterTerm = $list->quote("%".mb_strtolower($this->getParam("filter"))."%");

                $condition = "(`from` LIKE " . $filterTerm . " OR
                                        `to` LIKE " . $filterTerm . " OR
                                        `cc` LIKE " . $filterTerm . " OR
                                        `bcc` LIKE " . $filterTerm . " OR
                                        `subject` LIKE " . $filterTerm . " OR
                                        `params` LIKE " . $filterTerm . ")";

                if ($this->getParam('documentId')) {
                    $condition .= "AND documentId = " . (int)$this->getParam('documentId');
                }

                $list->setCondition($condition);
            }
        }

        $list->setOrder("DESC");

        $data = $list->load();
        $jsonData = [];

        if (is_array($data)) {
            foreach ($data as $entry) {
                $tmp = (array)get_object_vars($entry);
                unset($tmp['bodyHtml']);
                unset($tmp['bodyText']);
                $jsonData[] = $tmp;
            }
        }

        $this->_helper->json([
            "data" => $jsonData,
            "success" => true,
            "total" => $list->getTotalCount()
        ]);
    }

    /**
     * Shows the email logs and returns the Json object for the dynamic params
     */
    public function showEmailLogAction()
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $type = $this->getParam('type');
        $emailLog = Tool\Email\Log::getById($this->getParam('id'));

        if ($this->getParam('type') == 'text') {
            $this->disableViewAutoRender();
            echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body{background-color:#fff;}</style></head><body><pre>' . $emailLog->getTextLog() . '</pre></body></html>';
        } elseif ($this->getParam('type') == 'html') {
            $this->disableViewAutoRender();
            echo $emailLog->getHtmlLog();
        } elseif ($this->getParam('type') == 'params') {
            $this->disableViewAutoRender();
            try {
                $params = \Zend_Json::decode($emailLog->getParams());
            } catch (\Exception $e) {
                Logger::warning("Could not decode JSON param string");
                $params = [];
            }
            foreach ($params as &$entry) {
                $this->enhanceLoggingData($entry);
            }
            $this->_helper->json($params);
        } else {
            die('No Type specified');
        }
    }

    /**
     * Helper to build the correct Json array for the treeGrid
     *
     * @param array $data
     * @param $fullEntry
     */
    protected function enhanceLoggingData(&$data, &$fullEntry = null)
    {
        if (!empty($data['objectClass'])) {
            $class = "\\" . ltrim($data['objectClass'], "\\");
            if (!empty($data['objectId']) && is_subclass_of($class, "\\Pimcore\\Model\\Element\\ElementInterface")) {
                $obj = $class::getById($data['objectId']);
                if (is_null($obj)) {
                    $data['objectPath'] = '';
                } else {
                    $data['objectPath'] = $obj->getRealFullPath();
                }
                //check for classmapping
                if (stristr($class, "\\Pimcore\\Model") === false) {
                    $niceClassName = "\\" . ltrim(get_parent_class($class), "\\");
                } else {
                    $niceClassName = $class;
                }
                $niceClassName = str_replace("\\Pimcore\\Model\\", "", $niceClassName);
                $niceClassName = str_replace("_", "\\", $niceClassName);

                $tmp = explode("\\", $niceClassName);
                if (in_array($tmp[0], ['Object', 'Document', 'Asset'])) {
                    $data['objectClassBase'] = $tmp[0];
                    $data['objectClassSubType'] = $tmp[1];
                }
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
            $data['iconCls'] = 'pimcore_icon_folder';
            $data['data'] = ['type' => 'simple', 'value' => 'Children (' . count($data['children']) . ')'];
        } else {
            //setting the icon class
            if (!$data['iconCls']) {
                if ($data['objectClassBase'] == 'Object') {
                    $fullEntry['iconCls'] = 'pimcore_icon_object';
                } elseif ($data['objectClassBase'] == 'Asset') {
                    switch ($data['objectClassSubType']) {
                        case 'Image':
                            $fullEntry['iconCls'] = 'pimcore_icon_image';
                            break;
                        case 'Video':
                            $fullEntry['iconCls'] = 'pimcore_icon_wmv';
                            break;
                        case 'Text':
                            $fullEntry['iconCls'] = 'pimcore_icon_txt';
                            break;
                        case 'Document':
                            $fullEntry['iconCls'] = 'pimcore_icon_pdf';
                            break;
                        default:
                            $fullEntry['iconCls'] = 'pimcore_icon_asset';
                    }
                } elseif (strpos($data['objectClass'], 'Document') === 0) {
                    $fullEntry['iconCls'] = 'pimcore_icon_' . strtolower($data['objectClassSubType']);
                } else {
                    $data['iconCls'] = 'pimcore_icon_text';
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
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById($this->getParam('id'));
        if ($emailLog instanceof Tool\Email\Log) {
            $emailLog->delete();
            $success = true;
        }
        $this->_helper->json([
            "success" => $success,
        ]);
    }

    /**
     * Resends the email to the recipients
     */
    public function resendEmailAction()
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById($this->getParam('id'));

        if ($emailLog instanceof Tool\Email\Log) {
            $mail = new Mail();
            $mail->preventDebugInformationAppending();
            $mail->disableLogging();
            $mail->setIgnoreDebugMode(true);

            if ($html = $emailLog->getHtmlLog()) {
                $mail->setBodyHtml($html);
            }

            if ($text = $emailLog->getTextLog()) {
                $mail->setBodyText($text);
            }

            $mail->setFrom($emailLog->getFrom());

            foreach ($emailLog->getToAsArray() as $entry) {
                $mail->addTo($entry['email'], $entry['name']);
            }

            foreach ($emailLog->getCcAsArray() as $entry) {
                $mail->addCc($entry['email'], $entry['name']);
            }

            foreach ($emailLog->getBccAsArray() as $entry) {
                $mail->addBcc($entry['email']);
            }
            $mail->setSubject($emailLog->getSubject());
            $mail->send();
            $success = true;
        }

        $this->_helper->json([
            "success" => $success,
        ]);
    }


    /* global functionalities */

    public function sendTestEmailAction()
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $mail = new Mail();
        $mail->addTo($this->getParam("to"));
        $mail->setSubject($this->getParam("subject"));
        $mail->setIgnoreDebugMode(true);

        if ($this->getParam("type") == "text") {
            $mail->setBodyText($this->getParam("content"));
        } else {
            $mail->setBodyHtml($this->getParam("content"));
        }

        $mail->send();

        $this->_helper->json([
            "success" => true,
        ]);
    }


    public function blacklistAction()
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        if ($this->getParam("data")) {
            $data = \Zend_Json::decode($this->getParam("data"));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    $value = trim($value);
                }
            }

            if ($this->getParam("xaction") == "destroy") {
                $address = Tool\Email\Blacklist::getByAddress($data);
                $address->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $address = Tool\Email\Blacklist::getByAddress($data["address"]);
                $address->setValues($data);
                $address->save();

                $this->_helper->json(["data" => $address, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                unset($data["id"]);

                $address = new Tool\Email\Blacklist();
                $address->setValues($data);
                $address->save();

                $this->_helper->json(["data" => $address, "success" => true]);
            }
        } else {
            // get list of routes

            $list = new Tool\Email\Blacklist\Listing();

            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }


            if ($this->getParam("filter")) {
                $list->setCondition("`address` LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }

            $data = $list->load();

            $this->_helper->json([
                "success" => true,
                "data" => $data,
                "total" => $list->getTotalCount()
            ]);
        }

        $this->_helper->json(false);
    }

    /**
     * @return null|\Zend_Mail_Storage_Abstract
     */
    protected function getBounceMailbox()
    {
        $mail = null;
        $config = \Pimcore\Config::getSystemConfig();

        if ($config->email->bounce->type == "Mbox") {
            $mail = new \Zend_Mail_Storage_Mbox([
                'filename' => $config->email->bounce->mbox
            ]);
        } elseif ($config->email->bounce->type == "Maildir") {
            $mail = new \Zend_Mail_Storage_Maildir([
                'dirname' => $config->email->bounce->maildir
            ]);
        } elseif ($config->email->bounce->type == "IMAP") {
            $mail = new \Zend_Mail_Storage_Imap([
                'host' => $config->email->bounce->imap->host,
                "port" => $config->email->bounce->imap->port,
                'user' => $config->email->bounce->imap->username,
                'password' => $config->email->bounce->imap->password,
                "ssl" => (bool) $config->email->bounce->imap->ssl
            ]);
        } else {
            // default
            $pathes = [
                "/var/mail/" . get_current_user(),
                "/var/spool/mail/" . get_current_user()
            ];

            foreach ($pathes as $path) {
                if (is_dir($path)) {
                    $mail = new \Zend_Mail_Storage_Maildir([
                        'dirname' => $path . "/"
                    ]);
                } elseif (is_file($path)) {
                    $mail = new \Zend_Mail_Storage_Mbox([
                        'filename' => $path
                    ]);
                }
            }
        }

        return $mail;
    }

    public function bounceMailInboxListAction()
    {
        $this->checkPermission("emails");

        $offset = ($this->getParam("start")) ? $this->getParam("start")+1 : 1;
        $limit = ($this->getParam("limit")) ? $this->getParam("limit") : 40;

        $mail = $this->getBounceMailbox();
        $mail->seek($offset);

        $mails = [];
        $count = 0;
        while ($mail->valid()) {
            $count++;

            $message = $mail->current();

            $mailData = [
                "subject" => iconv(mb_detect_encoding($message->subject), "UTF-8", $message->subject),
                "to" => $message->to,
                "from" => $message->from,
                "id" => (int) $mail->key()
            ];

            $date = new \DateTime();
            $date->setTimestamp($message->date);
            $mailData["date"] = $date->format("Y-m-d");

            $mails[] = $mailData;

            if ($count >= $limit) {
                break;
            }

            $mail->next();
        }

        $this->_helper->json([
            "data" => $mails,
            "success" => true,
            "total" => $mail->countMessages()
        ]);
    }

    public function bounceMailInboxDetailAction()
    {
        $this->checkPermission("emails");

        $mail = $this->getBounceMailbox();

        $message = $mail->getMessage((int) $this->getParam("id"));
        $message->getContent();

        $this->view->mail = $mail; // we have to pass $mail too, otherwise the stream is closed
        $this->view->message = $message;
    }
}
