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
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
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
        $snippet = Document\Snippet::getById($request->get('id'));

        if (!$snippet) {
            throw $this->createNotFoundException('Snippet not found');
        }

        // check for lock
        if ($snippet->isAllowed('save') || $snippet->isAllowed('publish') || $snippet->isAllowed('unpublish') || $snippet->isAllowed('delete')) {
            if (Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            Element\Editlock::lock($request->get('id'), 'document');
        }

        $snippet = clone $snippet;
        $snippet = $this->getLatestVersion($snippet);

        $versions = Element\Service::getSafeVersionInfo($snippet->getVersions());
        $snippet->setVersions(array_splice($versions, -1, 1));
        $snippet->getScheduledTasks();
        $snippet->setLocked($snippet->isLocked());
        $snippet->setParent(null);

        $this->addTranslationsData($snippet);
        $this->minimizeProperties($snippet);

        // unset useless data
        $snippet->setElements(null);

        $data = $snippet->getObjectVars();
        $data['url'] = $snippet->getUrl();
        if ($snippet->getContentMasterDocument()) {
            $data['contentMasterDocumentPath'] = $snippet->getContentMasterDocument()->getRealFullPath();
        }

        $this->preSendDataActions($data, $snippet);

        if ($snippet->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
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
        $snippet = Document\Snippet::getById($request->get('id'));

        if (!$snippet) {
            throw $this->createNotFoundException('Snippet not found');
        }

        /** @var Document\Snippet|null $snippetSession */
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

        if (($request->get('task') == 'publish' && $snippet->isAllowed('publish')) || ($request->get('task') == 'unpublish' && $snippet->isAllowed('unpublish'))) {
            $this->setValuesToDocument($request, $snippet);

            $snippet->save();
            $this->saveToSession($snippet);

            $this->addAdminStyle($snippet, ElementAdminStyleEvent::CONTEXT_EDITOR, $treeData);

            return $this->adminJson([
                'success' => true,
                'data' => [
                    'versionDate' => $snippet->getModificationDate(),
                    'versionCount' => $snippet->getVersionCount()
                ],
                'treeData' => $treeData
            ]);
        } elseif ($snippet->isAllowed('save')) {
            $this->setValuesToDocument($request, $snippet);

            $snippet->saveVersion();
            $this->saveToSession($snippet);

            return $this->adminJson(['success' => true]);
        } else {
            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @param Request $request
     * @param Document $snippet
     */
    protected function setValuesToDocument(Request $request, Document $snippet)
    {
        $this->addSettingsToDocument($request, $snippet);
        $this->addDataToDocument($request, $snippet);
        $this->applySchedulerDataToElement($request, $snippet);
        $this->addPropertiesToDocument($request, $snippet);
    }
}
