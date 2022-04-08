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

use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/folder", name="pimcore_admin_document_folder_")
 *
 * @internal
 */
class FolderController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id", name="getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $folder = Document\Folder::getById($request->get('id'));
        if (!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        if (($lock = $this->checkForLock($folder)) instanceof JsonResponse) {
            return $lock;
        }

        $folder = clone $folder;
        $folder->setLocked($folder->isLocked());
        $folder->setParent(null);

        $data = $folder->getObjectVars();

        $this->addTranslationsData($folder, $data);
        $this->minimizeProperties($folder, $data);

        return $this->preSendDataActions($data, $folder);
    }

    /**
     * @Route("/save", name="save", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request): JsonResponse
    {
        $folder = Document\Folder::getById($request->get('id'));

        if (!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $folder->setModificationDate(time());
        $folder->setUserModification($this->getAdminUser()->getId());

        if ($folder->isAllowed('publish')) {
            $this->setValuesToDocument($request, $folder);
            $folder->save();

            $treeData = $this->getTreeNodeConfig($folder);

            return $this->adminJson(['success' => true, 'treeData' => $treeData]);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param Request $request
     * @param Document $folder
     */
    protected function setValuesToDocument(Request $request, Document $folder)
    {
        $this->addPropertiesToDocument($request, $folder);
    }
}
