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
     *
     * @throws \Exception
     */
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $folder = Document\Folder::getById((int)$request->get('id'));
        if (!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $folder = clone $folder;
        $folder->setParent(null);

        $data = $folder->getObjectVars();
        $data['locked'] = $folder->isLocked();

        $this->addTranslationsData($folder, $data);
        $this->minimizeProperties($folder, $data);
        $this->populateUsersNames($folder, $data);

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
        $folder = Document\Folder::getById((int) $request->get('id'));
        if (!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $result = $this->saveDocument($folder, $request, false, self::TASK_PUBLISH);
        /** @var Document\Folder $folder */
        $folder = $result[1];
        $treeData = $this->getTreeNodeConfig($folder);

        return $this->adminJson(['success' => true, 'treeData' => $treeData]);
    }

    protected function setValuesToDocument(Request $request, Document $document): void
    {
        $this->addPropertiesToDocument($request, $document);
    }
}
