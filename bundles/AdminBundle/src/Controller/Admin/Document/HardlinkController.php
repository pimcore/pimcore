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
use Pimcore\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/hardlink", name="pimcore_admin_document_hardlink_")
 *
 * @internal
 */
class HardlinkController extends DocumentControllerBase
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
        $link = Document\Hardlink::getById((int)$request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Hardlink not found');
        }

        if (($lock = $this->checkForLock($link, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $link = clone $link;
        $link->setParent(null);

        $data = $link->getObjectVars();
        $data['locked'] = $link->isLocked();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $link->getScheduledTasks()
        );

        $this->addTranslationsData($link, $data);
        $this->minimizeProperties($link, $data);
        $this->populateUsersNames($link, $data);

        if ($link->getSourceDocument()) {
            $data['sourcePath'] = $link->getSourceDocument()->getRealFullPath();
        }

        return $this->preSendDataActions($data, $link);
    }

    /**
     * @Route("/save", name="save", methods={"POST", "PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request): JsonResponse
    {
        $link = Document\Hardlink::getById((int) $request->get('id'));
        if (!$link) {
            throw $this->createNotFoundException('Hardlink not found');
        }

        $result = $this->saveDocument($link, $request);
        /** @var Document\Hardlink $link */
        $link = $result[1];
        $treeData = $this->getTreeNodeConfig($link);

        return $this->adminJson([
            'success' => true,
            'data' => [
                'versionDate' => $link->getModificationDate(),
                'versionCount' => $link->getVersionCount(),
            ],
            'treeData' => $treeData,
        ]);
    }

    /**
     * @param Request $request
     * @param Document\Hardlink $document
     */
    protected function setValuesToDocument(Request $request, Document $document): void
    {
        // data
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            $sourceId = null;
            if ($sourceDocument = Document::getByPath($data['sourcePath'])) {
                $sourceId = $sourceDocument->getId();
            }
            $document->setSourceId($sourceId);
            $document->setValues($data);
        }

        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document);
    }
}
