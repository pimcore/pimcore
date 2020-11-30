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
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Element;
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
     * @Route("/save-to-session", name="pimcore_admin_document_page_savetosession", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function saveToSessionAction(Request $request)
    {
        return parent::saveToSessionAction($request);
    }

    /**
     * @Route("/remove-from-session", name="pimcore_admin_document_page_removefromsession", methods={"DELETE"})
     *
     * {@inheritDoc}
     */
    public function removeFromSessionAction(Request $request)
    {
        return parent::removeFromSessionAction($request);
    }

    /**
     * @Route("/change-master-document", name="pimcore_admin_document_page_changemasterdocument", methods={"PUT"})
     *
     * {@inheritDoc}
     */
    public function changeMasterDocumentAction(Request $request)
    {
        return parent::changeMasterDocumentAction($request);
    }

    /**
     * @Route("/get-data-by-id", name="pimcore_admin_document_page_getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        $page = Document\Page::getById($request->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        // check for lock
        if ($page->isAllowed('save') || $page->isAllowed('publish') || $page->isAllowed('unpublish') || $page->isAllowed('delete')) {
            if (Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            Element\Editlock::lock($request->get('id'), 'document');
        }

        $page = clone $page;
        $isLatestVersion = true;
        $page = $this->getLatestVersion($page, $isLatestVersion);

        $pageVersions = Element\Service::getSafeVersionInfo($page->getVersions());
        $page->setVersions(array_splice($pageVersions, -1, 1));
        $page->getScheduledTasks();
        $page->setLocked($page->isLocked());
        $page->setParent(null);

        // unset useless data
        $page->setEditables(null);
        $page->setChildren(null);

        $data = $page->getObjectVars();

        $this->addTranslationsData($page, $data);
        $this->minimizeProperties($page, $data);

        if ($page->getContentMasterDocument()) {
            $data['contentMasterDocumentPath'] = $page->getContentMasterDocument()->getRealFullPath();
        }

        $data['url'] = $page->getUrl();
        $data['documentFromVersion'] = !$isLatestVersion;

        $this->preSendDataActions($data, $page);

        if ($page->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/save", name="pimcore_admin_document_page_save", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        $page = Document\Page::getById($request->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        /** @var Document\Page|null $pageSession */
        $pageSession = $this->getFromSession($page);

        if ($pageSession) {
            $page = $pageSession;
        } else {
            /** @var Document\Page $page */
            $page = $this->getLatestVersion($page);
        }

        $page->setUserModification($this->getAdminUser()->getId());

        if ($request->get('missingRequiredEditable') !== null) {
            $page->setMissingRequiredEditable(($request->get('missingRequiredEditable') == 'true') ? true : false);
        }

        $settings = [];
        if ($request->get('settings')) {
            $settings = $this->decodeJson($request->get('settings'));
            if ($settings['published'] ?? false) {
                $page->setMissingRequiredEditable(null);
            }
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

            if ($request->get('task') == 'unpublish') {
                $page->setPublished(false);
            } elseif ($request->get('task') == 'publish') {
                $page->setPublished(true);
            }

            $page->save();
            $this->saveToSession($page);

            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson([
                'success' => true,
                'treeData' => $treeData,
                'data' => [
                    'versionDate' => $page->getModificationDate(),
                    'versionCount' => $page->getVersionCount(),
                ],
            ]);
        } elseif ($page->isAllowed('save')) {
            $this->setValuesToDocument($request, $page);

            $page->saveVersion();
            $this->saveToSession($page);

            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson(['success' => true, 'treeData' => $treeData]);
        } else {
            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @Route("/get-list", name="pimcore_admin_document_page_getlist", methods={"GET"})
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
            'data' => $data,
        ]);
    }

    /**
     * @Route("/generate-screenshot", name="pimcore_admin_document_page_generatescreenshot", methods={"POST"})
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
        $document = Document\Page::getById($request->get('id'));
        if ($document instanceof Document\Page) {
            return new BinaryFileResponse($document->getPreviewImageFilesystemPath((bool) $request->get('hdpi')), 200, ['Content-Type' => 'image/jpg']);
        }

        throw $this->createNotFoundException('Page not found');
    }

    /**
     * @Route("/check-pretty-url", name="pimcore_admin_document_page_checkprettyurl", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkPrettyUrlAction(Request $request)
    {
        $docId = $request->get('id');
        $path = (string) trim($request->get('path'));

        $success = true;

        if ($path === '') {
            return $this->adminJson([
                'success' => $success,
            ]);
        }

        $message = [];
        $path = rtrim($path, '/');

        // must start with /
        if ($path !== '' && strpos($path, '/') !== 0) {
            $success = false;
            $message[] = 'URL must start with /.';
        }

        if (strlen($path) < 2) {
            $success = false;
            $message[] = 'URL must be at least 2 characters long.';
        }

        if (!Element\Service::isValidPath($path, 'document')) {
            $success = false;
            $message[] = 'URL is invalid.';
        }

        $list = new Document\Listing();
        $list->setCondition('(CONCAT(path, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
            AND id != ?', [
            $path, $path, $docId,
        ]);

        if ($list->getTotalCount() > 0) {
            $success = false;
            $message[] = 'URL path already exists.';
        }

        return $this->adminJson([
            'success' => $success,
            'message' => implode('<br>', $message),
        ]);
    }

    /**
     * @Route("/clear-editable-data", name="pimcore_admin_document_page_cleareditabledata", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearEditableDataAction(Request $request)
    {
        $targetGroupId = $request->get('targetGroup');
        $docId = $request->get('id');

        $doc = Document\PageSnippet::getById($docId);

        if (!$doc) {
            throw $this->createNotFoundException('Document not found');
        }

        foreach ($doc->getEditables() as $editable) {
            if ($targetGroupId && $doc instanceof TargetingDocumentInterface) {
                // remove target group specific elements
                if (preg_match('/^' . preg_quote($doc->getTargetGroupEditablePrefix($targetGroupId), '/') . '/', $editable->getName())) {
                    $doc->removeEditable($editable->getName());
                }
            } else {
                // remove all but target group data
                if (!preg_match('/^' . preg_quote(TargetingDocumentInterface::TARGET_GROUP_ELEMENT_PREFIX, '/') . '/', $editable->getName())) {
                    $doc->removeEditable($editable->getName());
                }
            }
        }

        $this->saveToSession($doc, true);

        return $this->adminJson([
            'success' => true,
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
        $this->applySchedulerDataToElement($request, $page);
    }
}
