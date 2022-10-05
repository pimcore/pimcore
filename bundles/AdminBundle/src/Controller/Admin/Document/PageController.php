<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Redirect;
use Pimcore\Model\Schedule\Task;
use Pimcore\Templating\Renderer\EditableRenderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @Route("/page", name="pimcore_admin_document_page_")
 *
 * @internal
 */
class PageController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id", name="getdatabyid", methods={"GET"})
     *
     * @param Request $request
     * @param StaticPageGenerator $staticPageGenerator
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getDataByIdAction(Request $request, StaticPageGenerator $staticPageGenerator): JsonResponse
    {
        $page = Document\Page::getById((int)$request->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        if (($lock = $this->checkForLock($page)) instanceof JsonResponse) {
            return $lock;
        }

        $page = clone $page;
        $draftVersion = null;
        $page = $this->getLatestVersion($page, $draftVersion);

        $pageVersions = Element\Service::getSafeVersionInfo($page->getVersions());
        $page->setVersions(array_splice($pageVersions, -1, 1));
        $page->setParent(null);

        // unset useless data
        $page->setEditables(null);
        $page->setChildren(null);

        $data = $page->getObjectVars();
        $data['locked'] = $page->isLocked();

        $this->addTranslationsData($page, $data);
        $this->minimizeProperties($page, $data);

        if ($page->getContentMasterDocument()) {
            $data['contentMasterDocumentPath'] = $page->getContentMasterDocument()->getRealFullPath();
        }

        if ($page->getStaticGeneratorEnabled()) {
            $data['staticLastGenerated'] = $staticPageGenerator->getLastModified($page);
        }

        $data['url'] = $page->getUrl();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $page->getScheduledTasks()
        );

        return $this->preSendDataActions($data, $page, $draftVersion);
    }

    /**
     * @Route("/save", name="save", methods={"PUT", "POST"})
     *
     * @param Request $request
     * @param StaticPageGenerator $staticPageGenerator
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request, StaticPageGenerator $staticPageGenerator): JsonResponse
    {
        $oldPage = Document\Page::getById((int) $request->get('id'));
        if (!$oldPage) {
            throw $this->createNotFoundException('Page not found');
        }

        /** @var Document\Page|null $pageSession */
        $pageSession = $this->getFromSession($oldPage);

        if ($pageSession) {
            $page = $pageSession;
        } else {
            $page = $this->getLatestVersion($oldPage);
        }

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

        list($task, $page, $version) = $this->saveDocument($page, $request);

        if ($task === self::TASK_PUBLISH || $task === self::TASK_UNPUBLISH) {
            $treeData = $this->getTreeNodeConfig($page);

            $data = [
                'versionDate' => $page->getModificationDate(),
                'versionCount' => $page->getVersionCount(),
            ];

            if ($staticGeneratorEnabled = $page->getStaticGeneratorEnabled()) {
                $data['staticGeneratorEnabled'] = $staticGeneratorEnabled;
                $data['staticLastGenerated'] = $staticPageGenerator->getLastModified($page);
            }

            if ($page->getPrettyUrl() !== $oldPage->getPrettyUrl()
                && empty($oldPage->getPrettyUrl()) === false
                && empty($page->getPrettyUrl()) === false
            ) {
                $redirect = new Redirect();

                $redirect->setSource($oldPage->getPrettyUrl());
                $redirect->setTarget($page->getPrettyUrl());
                $redirect->setStatusCode(301);
                $redirect->setType(Redirect::TYPE_AUTO_CREATE);
                $redirect->save();
            }

            return $this->adminJson([
                'success' => true,
                'treeData' => $treeData,
                'data' => $data,
            ]);
        } else {
            $this->saveToSession($page);

            $draftData = [];
            if ($version) {
                $draftData = [
                    'id' => $version->getId(),
                    'modificationDate' => $version->getDate(),
                    'isAutoSave' => $version->isAutoSave(),
                ];
            }

            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson(['success' => true, 'treeData' => $treeData, 'draft' => $draftData]);
        }
    }

    /**
     * @Route("/generate-previews", name="generatepreviews", methods={"GET"})
     *
     * @param Request $request
     * @param MessageBusInterface $messengerBusPimcoreCore
     *
     * @return JsonResponse
     */
    public function generatePreviewsAction(Request $request, MessageBusInterface $messengerBusPimcoreCore): JsonResponse
    {
        $list = new Document\Listing();
        $list->setCondition('type = ?', ['page']);

        foreach ($list->loadIdList() as $docId) {
            $messengerBusPimcoreCore->dispatch(
                new GeneratePagePreviewMessage($docId, \Pimcore\Tool::getHostUrl())
            );

            break;
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/display-preview-image", name="display_preview_image", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function displayPreviewImageAction(Request $request): BinaryFileResponse
    {
        $document = Document\Page::getById((int) $request->get('id'));
        if ($document instanceof Document\Page) {
            return new BinaryFileResponse($document->getPreviewImageFilesystemPath(), 200, [
                'Content-Type' => 'image/jpg',
            ]);
        }

        throw $this->createNotFoundException('Page not found');
    }

    /**
     * @Route("/check-pretty-url", name="checkprettyurl", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkPrettyUrlAction(Request $request): JsonResponse
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
     * @Route("/clear-editable-data", name="cleareditabledata", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearEditableDataAction(Request $request): JsonResponse
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
                if (!preg_match('/^' . preg_quote(TargetingDocumentInterface::TARGET_GROUP_EDITABLE_PREFIX, '/') . '/', $editable->getName())) {
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
     * @Route("/qr-code", name="qrcode", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     *
     * @throws \Exception
     */
    public function qrCodeAction(Request $request): BinaryFileResponse
    {
        $page = Document\Page::getById((int) $request->query->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        $url = $page->getUrl();

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size($request->query->get('download') ? 4000 : 500)
            ->build();

        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/qr-code-' . uniqid() . '.png';
        $result->saveToFile($tmpFile);

        $response = new BinaryFileResponse($tmpFile);
        $response->headers->set('Content-Type', 'image/png');

        if ($request->query->get('download')) {
            $response->setContentDisposition('attachment', 'qrcode-preview.png');
        }

        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @Route("/areabrick-render-index-editmode", name="areabrick-render-index-editmode", methods={"POST"})
     *
     * @param Request $request
     * @param BlockStateStack $blockStateStack
     * @param EditmodeEditableDefinitionCollector $definitionCollector
     * @param Environment $twig
     * @param EditableRenderer $editableRenderer
     * @param DocumentResolver $documentResolver
     *
     * @return JsonResponse
     *
     * @throws NotFoundHttpException|\Exception
     *
     */
    public function areabrickRenderIndexEditmode(
        Request $request,
        BlockStateStack $blockStateStack,
        EditmodeEditableDefinitionCollector $definitionCollector,
        Environment $twig,
        EditableRenderer $editableRenderer,
        DocumentResolver $documentResolver
    ): JsonResponse {
        $blockStateStackData = json_decode($request->get('blockStateStack'), true);
        $blockStateStack->loadArray($blockStateStackData);

        $document = Document\PageSnippet::getById((int) $request->get('documentId'));
        if (!$document) {
            throw $this->createNotFoundException();
        }

        $document = clone $document;
        $document->setEditables([]);
        $documentResolver->setDocument($request, $document);

        $twig->addGlobal('document', $document);
        $twig->addGlobal('editmode', true);

        // we can't use EditmodeResolver::setForceEditmode() here, because it would also render included documents in editmode
        // so we use the attribute as a workaround
        $request->attributes->set(EditmodeResolver::ATTRIBUTE_EDITMODE, true);

        $areaBlockConfig = json_decode($request->get('areablockConfig'), true);
        /** @var Document\Editable\Areablock $areablock */
        $areablock = $editableRenderer->getEditable($document, 'areablock', $request->get('realName'), $areaBlockConfig, true);
        $areablock->setRealName($request->get('realName'));
        $areablock->setEditmode(true);
        $areaBrickData = json_decode($request->get('areablockData'), true);
        $areablock->setDataFromEditmode($areaBrickData);
        $htmlCode = trim($areablock->renderIndex($request->get('index'), true));

        return new JsonResponse([
            'editableDefinitions' => $definitionCollector->getDefinitions(),
            'htmlCode' => $htmlCode,
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
