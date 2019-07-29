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

use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/snippet")
 */
class SnippetController extends DocumentControllerBase
{
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
            return $this->adminJson([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'document')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'document');

        $snippet = Document\Snippet::getById($request->get('id'));
        $snippet = clone $snippet;
        $snippet = $this->getLatestVersion($snippet);

        $versions = Element\Service::getSafeVersionInfo($snippet->getVersions());
        $snippet->setVersions(array_splice($versions, 0, 1));
        $snippet->getScheduledTasks();
        $snippet->idPath = Element\Service::getIdPath($snippet);
        $snippet->setUserPermissions($snippet->getUserPermissions());
        $snippet->setLocked($snippet->isLocked());
        $snippet->setParent(null);
        $snippet->url = $snippet->getUrl();

        if ($snippet->getContentMasterDocument()) {
            $snippet->contentMasterDocumentPath = $snippet->getContentMasterDocument()->getRealFullPath();
        }

        $this->addTranslationsData($snippet);
        $this->minimizeProperties($snippet);

        // unset useless data
        $snippet->setElements(null);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = $snippet->getObjectVars();
        $data['versionDate'] = $snippet->getModificationDate();

        $data['php'] = [
            'classes' => array_merge([get_class($snippet)], array_values(class_parents($snippet))),
            'interfaces' => array_values(class_implements($snippet))
        ];

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $snippet
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($snippet->isAllowed('view')) {
            return $this->adminJson($data);
        }

        return $this->adminJson(false);
    }

    /**
     * @Route("/save", methods={"POST","PUT"})
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
                $snippet = Document\Snippet::getById($request->get('id'));

                $snippetSession = $this->getFromSession($snippet);

                if ($snippetSession) {
                    $snippet = $snippetSession;
                } else {
                    $snippet = $this->getLatestVersion($snippet);
                }

                $snippet->setUserModification($this->getAdminUser()->getId());

                if ($request->get('task') == 'unpublish') {
                    $snippet->setPublished(false);
                }
                if ($request->get('task') == 'publish') {
                    $snippet->setPublished(true);
                }

                if (($request->get('task') == 'publish' && $snippet->isAllowed('publish')) or ($request->get('task') == 'unpublish' && $snippet->isAllowed('unpublish'))) {
                    $this->setValuesToDocument($request, $snippet);

                    try {
                        $snippet->save();
                        $this->saveToSession($snippet);

                        return $this->adminJson(['success' => true, 'data' => ['versionDate' => $snippet->getModificationDate(),
                                                                               'versionCount' => $snippet->getVersionCount()]]);
                    } catch (\Exception $e) {
                        if ($e instanceof Element\ValidationException) {
                            throw $e;
                        }

                        return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    if ($snippet->isAllowed('save')) {
                        $this->setValuesToDocument($request, $snippet);

                        try {
                            $snippet->saveVersion();
                            $this->saveToSession($snippet);

                            return $this->adminJson(['success' => true]);
                        } catch (\Exception $e) {
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
     * @param $request
     * @param Document $snippet
     */
    protected function setValuesToDocument(Request $request, Document $snippet)
    {
        $this->addSettingsToDocument($request, $snippet);
        $this->addDataToDocument($request, $snippet);
        $this->addSchedulerToDocument($request, $snippet);
        $this->addPropertiesToDocument($request, $snippet);
    }
}
