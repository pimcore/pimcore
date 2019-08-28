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
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/page")
 */
class PageController extends DocumentControllerBase
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
        $page = Document\Page::getById($request->get('id'));

        // check for lock
        if($page->isAllowed('save') || $page->isAllowed('publish') || $page->isAllowed('unpublish') || $page->isAllowed('delete')) {
            if (Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            Element\Editlock::lock($request->get('id'), 'document');
        }

        /**
         * @var $page Document\Page
         */

        $page = clone $page;
        $page = $this->getLatestVersion($page);

        $pageVersions = Element\Service::getSafeVersionInfo($page->getVersions());
        $page->setVersions(array_splice($pageVersions, 0, 1));
        $page->getScheduledTasks();
        $page->idPath = Element\Service::getIdPath($page);
        $page->setUserPermissions($page->getUserPermissions());
        $page->setLocked($page->isLocked());
        $page->setParent(null);

        if ($page->getContentMasterDocument()) {
            $page->contentMasterDocumentPath = $page->getContentMasterDocument()->getRealFullPath();
        }

        $page->url = $page->getUrl();

        // unset useless data
        $page->setElements(null);
        $page->setChildren(null);

        $this->addTranslationsData($page);
        $this->minimizeProperties($page);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = $page->getObjectVars();
        $data['versionDate'] = $page->getModificationDate();

        $data['php'] = [
            'classes' => array_merge([get_class($page)], array_values(class_parents($page))),
            'interfaces' => array_values(class_implements($page))
        ];

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $page
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($page->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
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
        if ($request->get('id')) {
            $page = Document\Page::getById($request->get('id'));

            $pageSession = $this->getFromSession($page);

            if ($pageSession) {
                $page = $pageSession;
            } else {
                $page = $this->getLatestVersion($page);
            }

            $page->setUserModification($this->getAdminUser()->getId());

            if ($request->get('task') == 'unpublish') {
                $page->setPublished(false);
            }
            if ($request->get('task') == 'publish') {
                $page->setPublished(true);
            }

            $settings = [];
            if ($request->get('settings')) {
                $settings = $this->decodeJson($request->get('settings'));
            }

            // check if settings exist, before saving meta data
            if ($request->get('settings') && is_array($settings)) {
                $metaData = [];
                for ($i = 1; $i < 30; $i++) {
                    if (array_key_exists('metadata_' . $i, $settings)) {
                        $metaData[] = $settings['metadata_' . $i];
                    }
                }
                $page->setMetaData($metaData);
            }

            // only save when publish or unpublish
            if (($request->get('task') == 'publish' && $page->isAllowed('publish')) || ($request->get('task') == 'unpublish' && $page->isAllowed('unpublish'))) {
                $this->setValuesToDocument($request, $page);

                $page->save();
                $this->saveToSession($page);

                return $this->adminJson([
                    'success' => true,
                    'data' => [
                        'versionDate' => $page->getModificationDate(),
                        'versionCount' => $page->getVersionCount()
                    ]
                ]);
            } elseif ($page->isAllowed('save')) {
                $this->setValuesToDocument($request, $page);

                $page->saveVersion();
                $this->saveToSession($page);

                return $this->adminJson(['success' => true]);
            } else {
                throw $this->createAccessDeniedHttpException();
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/get-list", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getListAction(Request $request)
    {
        $list = new Document\Listing();
        $list->setCondition('type = ?', ['page']);
        $data = $list->loadIdPathList();

        return $this->adminJson([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @Route("/generate-screenshot", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function generateScreenshotAction(Request $request)
    {
        $success = false;
        if ($request->get('id')) {
            try {
                $success = Document\Service::generatePagePreview($request->get('id'), $request);
            } catch (\Exception $e) {
                Logger::err($e);
            }
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/display-preview-image", name="pimcore_admin_page_display_preview_image", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function displayPreviewImageAction(Request $request)
    {
        $document = Document::getById($request->get('id'));
        if ($document instanceof Document\Page) {
            return new BinaryFileResponse($document->getPreviewImageFilesystemPath((bool) $request->get('hdpi')), 200, ['Content-Type' => 'image/jpg']);
        }
    }

    /**
     * @Route("/check-pretty-url", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkPrettyUrlAction(Request $request)
    {
        $docId = $request->get('id');
        $path = trim($request->get('path'));
        $path = rtrim($path, '/');

        $success = true;

        // must start with /
        if (strpos($path, '/') !== 0) {
            $success = false;
        }

        if (strlen($path) < 2) {
            $success = false;
        }

        if (!Element\Service::isValidPath($path, 'document')) {
            $success = false;
        }

        $list = new Document\Listing();
        $list->setCondition('(CONCAT(path, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
            AND id != ?', [
            $path, $path, $docId
        ]);

        if ($list->getTotalCount() > 0) {
            $success = false;
        }

        return $this->adminJson([
            'success' => $success
        ]);
    }

    /**
     * @Route("/clear-editable-data", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearEditableDataAction(Request $request)
    {
        $targetGroupId = $request->get('targetGroup');
        $docId = $request->get('id');

        $doc = Document::getById($docId);

        /** @var Document\Tag $element */
        foreach ($doc->getElements() as $element) {
            if ($targetGroupId && $doc instanceof TargetingDocumentInterface) {
                // remove target group specific elements
                if (preg_match('/^' . preg_quote($doc->getTargetGroupElementPrefix($targetGroupId), '/') . '/', $element->getName())) {
                    $doc->removeElement($element->getName());
                }
            } else {
                // remove all but target group data
                if (!preg_match('/^' . preg_quote(TargetingDocumentInterface::TARGET_GROUP_ELEMENT_PREFIX, '/') . '/', $element->getName())) {
                    $doc->removeElement($element->getName());
                }
            }
        }

        $this->saveToSession($doc, true);

        return $this->adminJson([
            'success' => true
        ]);
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
