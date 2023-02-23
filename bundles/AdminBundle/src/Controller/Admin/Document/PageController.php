<?php
declare(strict_types=1);

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
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Localization\LocaleService;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Model\Schedule\Task;
use Pimcore\Templating\Renderer\EditableRenderer;
use Pimcore\Tool\Frontend;
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
    use RecursionBlockingEventDispatchHelperTrait;
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

        if (($lock = $this->checkForLock($page, $request->getSession()->getId())) instanceof JsonResponse) {
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
        $this->populateUsersNames($page, $data);

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
        $pageSession = $this->getFromSession($oldPage, $request->getSession());

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
        $arguments = [
            'oldPage' => $oldPage,
            'task' => $task
        ];
        $documentEvent = new DocumentEvent($page, $arguments);
        $this->dispatchEvent($documentEvent, DocumentEvents::PAGE_POST_SAVE_ACTION);
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

            return $this->adminJson([
                'success' => true,
                'treeData' => $treeData,
                'data' => $data,
            ]);
        } else {
            $this->saveToSession($page, $request->getSession());

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
        $list->setCondition('`type` = ?', ['page']);

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
        $docId = $request->request->getInt('id');
        $path = trim($request->request->get('path', ''));

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
        $list->setCondition('(CONCAT(`path`, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
            AND id != ?', [
            $path, $path, $docId,
        ]);

        if ($list->getTotalCount() > 0) {
            $checkDocument = Document::getById($docId);
            $checkSite     = Frontend::getSiteForDocument($checkDocument);
            $checkSiteId   = empty($checkSite) ? 0 : $checkSite->getId();

            foreach ($list as $document) {
                if (empty($document)) {
                    continue;
                }

                $site   = Frontend::getSiteForDocument($document);
                $siteId = empty($site) ? 0 : $site->getId();

                if ($siteId === $checkSiteId) {
                    $success   = false;
                    $message[] = 'URL path already exists.';

                    break;
                }
            }
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
        $docId = $request->request->getInt('id');
        $doc = Document\PageSnippet::getById($docId);

        if (!$doc) {
            throw $this->createNotFoundException('Document not found');
        }

        foreach ($doc->getEditables() as $editable) {
            // remove all but target group data
            // Hardcoded the TARGET_GROUP_EDITABLE_PREFIX prefix here as we shouldn't remove the bundle specific editables even if bundle is not enabled/installed
            if (!preg_match('/^' . preg_quote('persona_ -', '/') . '/', $editable->getName())) {
                $doc->removeEditable($editable->getName());
            }
        }

        $this->saveToSession($doc, $request->getSession(), true);

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
     * @param LocaleService $localeService
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
        DocumentResolver $documentResolver,
        LocaleService $localeService
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

        // setting locale manually here before rendering, to make sure editables use the right locale from document
        $localeService->setLocale($document->getProperty('language'));

        $areaBlockConfig = json_decode($request->get('areablockConfig'), true);
        /** @var Document\Editable\Areablock $areablock */
        $areablock = $editableRenderer->getEditable($document, 'areablock', $request->get('realName'), $areaBlockConfig, true);
        $areablock->setRealName($request->get('realName'));
        $areablock->setEditmode(true);
        $areaBrickData = json_decode($request->get('areablockData'), true);
        $areablock->setDataFromEditmode($areaBrickData);
        $htmlCode = trim($areablock->renderIndex((int) $request->get('index'), true));

        return new JsonResponse([
            'editableDefinitions' => $definitionCollector->getDefinitions(),
            'htmlCode' => $htmlCode,
        ]);
    }

    protected function setValuesToDocument(Request $request, Document $document): void
    {
        $this->addSettingsToDocument($request, $document);
        $this->addDataToDocument($request, $document);
        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document);
    }
}
