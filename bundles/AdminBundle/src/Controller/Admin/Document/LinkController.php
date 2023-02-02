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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/link", name="pimcore_admin_document_link_")
 *
 * @internal
 */
class LinkController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id", name="getdatabyid", methods={"GET"})
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getDataByIdAction(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $link = Document\Link::getById((int)$request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Link not found');
        }

        if (($lock = $this->checkForLock($link, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $link = clone $link;

        $link->setElement(null);
        $link->setParent(null);

        $data = $serializer->serialize($link->getObjectVars(), 'json', []);
        $data = json_decode($data, true);
        $data['locked'] = $link->isLocked();
        $data['rawHref'] = $link->getRawHref();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $link->getScheduledTasks()
        );

        $this->addTranslationsData($link, $data);
        $this->minimizeProperties($link, $data);
        $this->populateUsersNames($link, $data);

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
        $link = Document\Link::getById((int) $request->get('id'));
        if (!$link) {
            throw $this->createNotFoundException('Link not found');
        }

        $result = $this->saveDocument($link, $request);
        /** @var Document\Link $link */
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
     * @param Document\Link $document
     */
    protected function setValuesToDocument(Request $request, Document $document): void
    {
        // data
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            $path = $data['path'];

            if (!empty($path)) {
                $target = null;
                if ($data['linktype'] == 'internal' && $data['internalType']) {
                    $target = Element\Service::getElementByPath($data['internalType'], $path);
                    if ($target) {
                        $data['internal'] = $target->getId();
                    }
                }

                if (!$target) {
                    if ($target = Document::getByPath($path)) {
                        $data['linktype'] = 'internal';
                        $data['internalType'] = 'document';
                        $data['internal'] = $target->getId();
                    } elseif ($target = Asset::getByPath($path)) {
                        $data['linktype'] = 'internal';
                        $data['internalType'] = 'asset';
                        $data['internal'] = $target->getId();
                    } elseif ($target = Concrete::getByPath($path)) {
                        $data['linktype'] = 'internal';
                        $data['internalType'] = 'object';
                        $data['internal'] = $target->getId();
                    } else {
                        $data['linktype'] = 'direct';
                        $data['internalType'] = null;
                        $data['direct'] = $path;
                    }

                    if ($target) {
                        $data['linktype'] = 'internal';
                    }
                }
            } else {
                // clear content of link
                $data['linktype'] = 'internal';
                $data['direct'] = '';
                $data['internalType'] = null;
                $data['internal'] = null;
            }

            unset($data['path']);

            $document->setValues($data);
        }

        $this->addPropertiesToDocument($request, $document);
        $this->applySchedulerDataToElement($request, $document);
    }
}
