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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Mail;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Tool;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/email")
 */
class EmailController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {

        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'document')) {
            return $this->adminJson([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'document')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'document');

        $email = Document\Email::getById($request->get('id'));
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
        $data = object2array($email);
        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $email
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($email->isAllowed('view')) {
            return $this->adminJson($data);
        }

        return $this->adminJson(false);
    }

    /**
     * @Route("/save")
     * @Method({"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get('id')) {
                $page = Document\Email::getById($request->get('id'));

                $page = $this->getLatestVersion($page);
                $page->setUserModification($this->getAdminUser()->getId());

                if ($request->get('task') == 'unpublish') {
                    $page->setPublished(false);
                }
                if ($request->get('task') == 'publish') {
                    $page->setPublished(true);
                }
                // only save when publish or unpublish
                if (($request->get('task') == 'publish' && $page->isAllowed('publish')) or ($request->get('task') == 'unpublish' && $page->isAllowed('unpublish'))) {
                    $this->setValuesToDocument($request, $page);

                    try {
                        $page->save();
                        $this->saveToSession($page);

                        return $this->adminJson(['success' => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);

                        return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed('save')) {
                        $this->setValuesToDocument($request, $page);

                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);

                            return $this->adminJson(['success' => true]);
                        } catch (\Exception $e) {
                            if ($e instanceof Element\ValidationException) {
                                throw $e;
                            }

                            Logger::err($e);

                            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                return $this->adminJson(['success' => false, 'type' => 'ValidationException', 'message' => $e->getMessage(), 'stack' => $e->getTraceAsString(), 'code' => $e->getCode()]);
            }
            throw $e;
        }

        return $this->adminJson(false);
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
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function emailLogsAction(Request $request)
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $list = new Tool\Email\Log\Listing();
        if ($request->get('documentId')) {
            $list->setCondition('documentId = ' . (int)$request->get('documentId'));
        }
        $list->setLimit($request->get('limit'));
        $list->setOffset($request->get('start'));
        $list->setOrderKey('sentDate');

        if ($request->get('filter')) {
            if ($request->get('filter')) {
                $filterTerm = $request->get('filter');
                if ($filterTerm == '*') {
                    $filterTerm = '';
                }

                $filterTerm = str_replace('%', '*', $filterTerm);
                $filterTerm = str_replace('@', '#', $filterTerm);
                $filterTerm = htmlspecialchars($filterTerm, ENT_QUOTES);

                $condition = '( MATCH (`from`,`to`,`cc`,`bcc`,`subject`,`params`) AGAINST (' . $list->quote($filterTerm) . ' IN BOOLEAN MODE) )';

                if ($request->get('documentId')) {
                    $condition .= 'AND documentId = ' . (int)$request->get('documentId');
                }

                $list->setCondition($condition);
            }
        }

        $list->setOrder('DESC');

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

        return $this->adminJson([
            'data' => $jsonData,
            'success' => true,
            'total' => $list->getTotalCount()
        ]);
    }

    /**
     * @Route("/show-email-log")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function showEmailLogAction(Request $request)
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $type = $request->get('type');
        $emailLog = Tool\Email\Log::getById($request->get('id'));

        if ($request->get('type') == 'text') {
            $templatingEnginePhp = $this->get('pimcore.templating.engine.php');

            return new Response('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style>body{background-color:#fff;}</style></head><body><pre>' . $templatingEnginePhp->escape($emailLog->getTextLog()) . '</pre></body></html>');
        } elseif ($request->get('type') == 'html') {
            return new Response($emailLog->getHtmlLog());
        } elseif ($request->get('type') == 'params') {
            try {
                $params = $this->decodeJson($emailLog->getParams());
            } catch (\Exception $e) {
                Logger::warning('Could not decode JSON param string');
                $params = [];
            }
            foreach ($params as &$entry) {
                $this->enhanceLoggingData($entry);
            }

            return $this->adminJson($params);
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
            $class = '\\' . ltrim($data['objectClass'], '\\');
            if (!empty($data['objectId']) && is_subclass_of($class, '\\Pimcore\\Model\\Element\\ElementInterface')) {
                $obj = $class::getById($data['objectId']);
                if (is_null($obj)) {
                    $data['objectPath'] = '';
                } else {
                    $data['objectPath'] = $obj->getRealFullPath();
                }
                //check for classmapping
                if (stristr($class, '\\Pimcore\\Model') === false) {
                    $niceClassName = '\\' . ltrim(get_parent_class($class), '\\');
                } else {
                    $niceClassName = $class;
                }
                $niceClassName = str_replace('\\Pimcore\\Model\\', '', $niceClassName);
                $niceClassName = str_replace('_', '\\', $niceClassName);

                $tmp = explode('\\', $niceClassName);
                if (in_array($tmp[0], ['DataObject', 'Document', 'Asset'])) {
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
                if ($data['objectClassBase'] == 'DataObject') {
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
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function deleteEmailLogAction(Request $request)
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $success = false;
        $emailLog = Tool\Email\Log::getById($request->get('id'));
        if ($emailLog instanceof Tool\Email\Log) {
            $emailLog->delete();
            $success = true;
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/resend-email")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function resendEmailAction(Request $request)
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
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

            foreach ($emailLog->getReplyToAsArray() as $entry) {
                $mail->addReplyTo($entry['email']);
            }

            $mail->setSubject($emailLog->getSubject());

            // add document
            if ($emailLog->getDocumentId()) {
                $mail->setDocument($emailLog->getDocumentId());
            }

            // re-add params
            try {
                $params = $this->decodeJson($emailLog->getParams());
            } catch (\Exception $e) {
                Logger::warning('Could not decode JSON param string');
                $params = [];
            }

            foreach ($params as $entry) {
                $data = null;
                $hasChildren = isset($entry['children']) && is_array($entry['children']);

                if ($hasChildren) {
                    $childData = [];
                    foreach ($entry['children'] as $childParam) {
                        $childData[$childParam['key']] = $this->parseLoggingParamObject($childParam);
                    }
                    $data = $childData;
                } else {
                    $data = $this->parseLoggingParamObject($entry);
                }

                $mail->setParam($entry['key'], $data);
            }

            $mail->send();
            $success = true;
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/send-test-email")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function sendTestEmailAction(Request $request)
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        $mail = new Mail();
        $mail->addTo($request->get('to'));
        $mail->setSubject($request->get('subject'));
        $mail->setIgnoreDebugMode(true);

        if ($request->get('type') == 'text') {
            $mail->setBodyText($request->get('content'));
        } else {
            $mail->setBodyHtml($request->get('content'));
        }

        $mail->send();

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/blacklist")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function blacklistAction(Request $request)
    {
        if (!$this->getAdminUser()->isAllowed('emails')) {
            throw new \Exception("Permission denied, user needs 'emails' permission.");
        }

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
            }

            if ($request->get('xaction') == 'destroy') {
                $address = Tool\Email\Blacklist::getByAddress($data['address']);
                $address->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $address = Tool\Email\Blacklist::getByAddress($data['address']);
                $address->setValues($data);
                $address->save();

                return $this->adminJson(['data' => $address, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                unset($data['id']);

                $address = new Tool\Email\Blacklist();
                $address->setValues($data);
                $address->save();

                return $this->adminJson(['data' => $address, 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new Tool\Email\Blacklist\Listing();

            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings($request->query->all());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }

            if ($request->get('filter')) {
                $list->setCondition('`address` LIKE ' . $list->quote('%'.$request->get('filter').'%'));
            }

            $data = $list->load();

            return $this->adminJson([
                'success' => true,
                'data' => $data,
                'total' => $list->getTotalCount()
            ]);
        }

        return $this->adminJson(false);
    }

    /**
     * @param array $params
     *
     * @return $data
     */
    protected function parseLoggingParamObject($params)
    {
        $data = null;
        if ($params['data']['type'] === 'object') {
            $class = '\\' . ltrim($params['data']['objectClass'], '\\');
            if (!empty($params['data']['objectId']) && is_subclass_of($class, '\\Pimcore\\Model\\Element\\ElementInterface')) {
                $obj = $class::getById($params['data']['objectId']);
                if (!is_null($obj)) {
                    $data = $obj;
                }
            }
        } else {
            $data = $params['data']['value'];
        }

        return $data;
    }
}
