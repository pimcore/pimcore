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
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/hardlink")
 */
class HardlinkController extends DocumentControllerBase
{
    use ElementEditLockHelperTrait;

    /**
     * @Route("/save-to-session", name="pimcore_admin_document_hardlink_savetosession", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function saveToSessionAction(Request $request)
    {
        return parent::saveToSessionAction($request);
    }

    /**
     * @Route("/remove-from-session", name="pimcore_admin_document_hardlink_removefromsession", methods={"DELETE"})
     *
     * {@inheritDoc}
     */
    public function removeFromSessionAction(Request $request)
    {
        return parent::removeFromSessionAction($request);
    }

    /**
     * @Route("/change-master-document", name="pimcore_admin_document_hardlink_changemasterdocument", methods={"PUT"})
     *
     * {@inheritDoc}
     */
    public function changeMasterDocumentAction(Request $request)
    {
        return parent::changeMasterDocumentAction($request);
    }

    /**
     * @Route("/get-data-by-id", name="pimcore_admin_document_hardlink_getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        $link = Document\Hardlink::getById($request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Hardlink not found');
        }

        // check for lock
        if ($link->isAllowed('save') || $link->isAllowed('publish') || $link->isAllowed('unpublish') || $link->isAllowed('delete')) {
            if (Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            Element\Editlock::lock($request->get('id'), 'document');
        }

        $link = clone $link;
        $link->setLocked($link->isLocked());
        $link->setParent(null);
        $link->getScheduledTasks();

        $data = $link->getObjectVars();

        $this->addTranslationsData($link, $data);
        $this->minimizeProperties($link, $data);

        if ($link->getSourceDocument()) {
            $data['sourcePath'] = $link->getSourceDocument()->getRealFullPath();
        }

        $this->preSendDataActions($data, $link);

        if ($link->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/save", name="pimcore_admin_document_hardlink_save", methods={"POST", "PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        $link = Document\Hardlink::getById($request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Hardlink not found');
        }

        $this->setValuesToDocument($request, $link);

        $link->setModificationDate(time());
        $link->setUserModification($this->getAdminUser()->getId());

        if ($request->get('task') == 'unpublish') {
            $link->setPublished(false);
        }
        if ($request->get('task') == 'publish') {
            $link->setPublished(true);
        }

        // only save when publish or unpublish
        if (($request->get('task') == 'publish' && $link->isAllowed('publish')) || ($request->get('task') == 'unpublish' && $link->isAllowed('unpublish'))) {
            $link->save();

            $treeData = $this->getTreeNodeConfig($link);

            return $this->adminJson([
                'success' => true,
                 'data' => [
                     'versionDate' => $link->getModificationDate(),
                     'versionCount' => $link->getVersionCount(),
                 ],
                'treeData' => $treeData,
            ]);
        } else {
            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @param Request $request
     * @param Document\Hardlink $link
     */
    protected function setValuesToDocument(Request $request, Document $link)
    {
        // data
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            $sourceId = null;
            if ($sourceDocument = Document::getByPath($data['sourcePath'])) {
                $sourceId = $sourceDocument->getId();
            }
            $link->setSourceId($sourceId);
            $link->setValues($data);
        }

        $this->addPropertiesToDocument($request, $link);
        $this->applySchedulerDataToElement($request, $link);
    }
}
