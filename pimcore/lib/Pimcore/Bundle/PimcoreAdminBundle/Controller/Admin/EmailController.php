<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreBundle\Configuration\TemplatePhp;
use Pimcore\Mail;
use Pimcore\Model\Element;
use Pimcore\Model\Document;
use Pimcore\Model\Tool;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/email")
 */
class EmailController extends DocumentControllerBase
{

    /**
     * @Route("/get-data-by-id")
     * @param Request $request
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {

        // check for lock
        if (Element\Editlock::isLocked($request->get("id"), "document")) {
            return $this->json([
                "editlock" => Element\Editlock::getByElement($request->get("id"), "document")
            ]);
        }
        Element\Editlock::lock($request->get("id"), "document");

        $email = Document\Email::getById($request->get("id"));
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
            return $this->json($returnValueContainer->getData());
        }

        return $this->json(false);
    }

    /**
     * @Route("/save")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get("id")) {
                $page = Document\Email::getById($request->get("id"));

                $page = $this->getLatestVersion($page);
                $page->setUserModification($this->getUser()->getId());

                if ($request->get("task") == "unpublish") {
                    $page->setPublished(false);
                }
                if ($request->get("task") == "publish") {
                    $page->setPublished(true);
                }
                // only save when publish or unpublish
                if (($request->get("task") == "publish" && $page->isAllowed("publish")) or ($request->get("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                    $this->setValuesToDocument($request, $page);


                    try {
                        $page->save();
                        $this->saveToSession($page);
                        return $this->json(["success" => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);
                        return $this->json(["success" => false, "message" => $e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed("save")) {
                        $this->setValuesToDocument($request, $page);


                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);
                            return $this->json(["success" => true]);
                        } catch (\Exception $e) {
                            if ($e instanceof Element\ValidationException) {
                                throw $e;
                            }

                            Logger::err($e);
                            return $this->json(["success" => false, "message" => $e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                return $this->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }

        return $this->json(false);
    }

    /**
     * @param Request $request
     * @param Document $page
     */
    protected function setValuesToDocument(Request $request, Document $page)
    {
        $this->addSettingsToDocument($request, $page);
        $this->addDataToDocument($request, $page);
        $this->addPropertiesToDocument($request, $page);
        $this->addSchedulerToDocument($request, $page);
    }

    /**
     * @Route("/email-logs")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function emailLogsAction(Request $request)
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $list = new Tool\Email\Log\Listing();
        if ($request->get('documentId')) {
            $list->setCondition('documentId = ' . (int)$request->get('documentId'));
        }
        $list->setLimit($request->get("limit"));
        $list->setOffset($request->get("start"));
        $list->setOrderKey("sentDate");

        if ($request->get('filter')) {
            if ($request->get("filter")) {
                $filterTerm = $list->quote("%".mb_strtolower($request->get("filter"))."%");

                $condition = "(`from` LIKE " . $filterTerm . " OR
                                        `to` LIKE " . $filterTerm . " OR
                                        `cc` LIKE " . $filterTerm . " OR
                                        `bcc` LIKE " . $filterTerm . " OR
                                        `subject` LIKE " . $filterTerm . " OR
                                        `params` LIKE " . $filterTerm . ")";

                if ($request->get('documentId')) {
                    $condition .= "AND documentId = " . (int)$request->get('documentId');
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

        return $this->json([
            "data" => $jsonData,
            "success" => true,
            "total" => $list->getTotalCount()
        ]);
    }


    /**
     * @Route("/show-email-log")
     * @param Request $request
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function showEmailLogAction(Request $request)
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $type = $request->get('type');
        $emailLog = Tool\Email\Log::getById($request->get('id'));

        if ($request->get('type') == 'text') {

            return new Response('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body{background-color:#fff;}</style></head><body><pre>' . $emailLog->getTextLog() . '</pre></body></html>');
        } elseif ($request->get('type') == 'html') {
            return new Response($emailLog->getHtmlLog());
        } elseif ($request->get('type') == 'params') {
            try {
                $params = \Zend_Json::decode($emailLog->getParams());
            } catch (\Exception $e) {
                Logger::warning("Could not decode JSON param string");
                $params = [];
            }
            foreach ($params as &$entry) {
                $this->enhanceLoggingData($entry);
            }
            return $this->json($params);
        } else {
            return new Response('No Type specified');
        }
    }

    /**
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
     * @Route("/delete-email-log")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteEmailLogAction(Request $request)
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById($request->get('id'));
        if ($emailLog instanceof Tool\Email\Log) {
            $emailLog->delete();
            $success = true;
        }
        return $this->json([
            "success" => $success,
        ]);
    }

    /**
     * @Route("/resend-email")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function resendEmailAction(Request $request)
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById($request->get('id'));

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

        return $this->json([
            "success" => $success,
        ]);
    }


    /**
     * @Route("/send-test-email")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function sendTestEmailAction(Request $request)
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $mail = new Mail();
        $mail->addTo($request->get("to"));
        $mail->setSubject($request->get("subject"));
        $mail->setIgnoreDebugMode(true);

        if ($request->get("type") == "text") {
            $mail->setBodyText($request->get("content"));
        } else {
            $mail->setBodyHtml($request->get("content"));
        }

        $mail->send();

        return $this->json([
            "success" => true,
        ]);
    }


    /**
     * @Route("/blacklist")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function blacklistAction(Request $request)
    {
        if (!$this->getUser()->isAllowed("emails")) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        if ($request->get("data")) {
            $data = \Zend_Json::decode($request->get("data"));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    $value = trim($value);
                }
            }

            if ($request->get("xaction") == "destroy") {
                $address = Tool\Email\Blacklist::getByAddress($data);
                $address->delete();

                return $this->json(["success" => true, "data" => []]);
            } elseif ($request->get("xaction") == "update") {
                $address = Tool\Email\Blacklist::getByAddress($data["address"]);
                $address->setValues($data);
                $address->save();

                return $this->json(["data" => $address, "success" => true]);
            } elseif ($request->get("xaction") == "create") {
                unset($data["id"]);

                $address = new Tool\Email\Blacklist();
                $address->setValues($data);
                $address->save();

                return $this->json(["data" => $address, "success" => true]);
            }
        } else {
            // get list of routes

            $list = new Tool\Email\Blacklist\Listing();

            $list->setLimit($request->get("limit"));
            $list->setOffset($request->get("start"));

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }


            if ($request->get("filter")) {
                $list->setCondition("`address` LIKE " . $list->quote("%".$request->get("filter")."%"));
            }

            $data = $list->load();

            return $this->json([
                "success" => true,
                "data" => $data,
                "total" => $list->getTotalCount()
            ]);
        }

        return $this->json(false);
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

    /**
     * @Route("/bounce-mail-inbox-list")
     * @param Request $request
     * @return JsonResponse
     */
    public function bounceMailInboxListAction(Request $request)
    {
        $this->checkPermission("emails");

        $offset = ($request->get("start")) ? $request->get("start")+1 : 1;
        $limit = ($request->get("limit")) ? $request->get("limit") : 40;

        $mails = [];

        $mail = $this->getBounceMailbox();
        if ($mail) {
            $mail->seek($offset);

            $count = 0;
            while ($mail->valid()) {
                $count++;

                $message = $mail->current();

                $mailData = [
                    "subject" => iconv(mb_detect_encoding($message->subject), "UTF-8", $message->subject),
                    "to" => $message->to,
                    "from" => $message->from,
                    "id" => (int)$mail->key()
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
        }

        return $this->json([
            "data" => $mails,
            "success" => true,
            "total" => $mail ? $mail->countMessages() : 0
        ]);
    }

    /**
     * @Route("/bounce-mail-inbox-detail")
     * @TemplatePhp()
     * @param Request $request
     * @return array
     */
    public function bounceMailInboxDetailAction(Request $request)
    {
        $this->checkPermission("emails");

        $mail = $this->getBounceMailbox();

        $message = $mail->getMessage((int) $request->get("id"));
        $message->getContent();

        return [
            "mail" => $mail,
            "message" => $message
        ];
    }
}
