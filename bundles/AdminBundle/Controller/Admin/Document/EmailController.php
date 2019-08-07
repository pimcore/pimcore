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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Pimcore\Controller\Traits\ElementEditLockHelperTrait;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/email")
 */
class EmailController extends DocumentControllerBase
{
    use ElementEditLockHelperTrait;

    /**
     * @Route("/get-data-by-id", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {

        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'document')) {
            return $this->getEditLockResponse($request->get('id'), 'document');
        }
        Element\Editlock::lock($request->get('id'), 'document');

        $email = Document\Email::getById($request->get('id'));
        $email = clone $email;
        $email = $this->getLatestVersion($email);

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, 0, 1));
        $email->idPath = Element\Service::getIdPath($email);
        $email->setUserPermissions($email->getUserPermissions());
        $email->setLocked($email->isLocked());
        $email->setParent(null);
        $email->url = $email->getUrl();

        // unset useless data
        $email->setElements(null);
        $email->setChildren(null);

        $this->addTranslationsData($email);
        $this->minimizeProperties($email);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = $email->getObjectVars();
        $data['versionDate'] = $email->getModificationDate();

        $data['php'] = [
            'classes' => array_merge([get_class($email)], array_values(class_parents($email))),
            'interfaces' => array_values(class_implements($email))
        ];

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
     * @Route("/save", methods={"PUT", "POST"})
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

                        return $this->adminJson(['success' => true,
                                                 'data' => ['versionDate' => $page->getModificationDate(),
                                                            'versionCount' => $page->getVersionCount()]]);
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
}
