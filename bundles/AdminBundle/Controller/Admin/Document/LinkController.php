<?php

/**
 * Pimcore.
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

use Pimcore\Controller\Traits\ElementEditLockHelperTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Schedule\Task;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/link")
 */
class LinkController extends DocumentControllerBase
{
    use ElementEditLockHelperTrait;

    /**
     * @Route("/save-to-session", name="pimcore_admin_document_link_savetosession", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function saveToSessionAction(Request $request)
    {
        return parent::saveToSessionAction($request);
    }

    /**
     * @Route("/remove-from-session", name="pimcore_admin_document_link_removefromsession", methods={"DELETE"})
     *
     * {@inheritDoc}
     */
    public function removeFromSessionAction(Request $request)
    {
        return parent::removeFromSessionAction($request);
    }

    /**
     * @Route("/change-master-document", name="pimcore_admin_document_link_changemasterdocument", methods={"PUT"})
     *
     * {@inheritDoc}
     */
    public function changeMasterDocumentAction(Request $request)
    {
        return parent::changeMasterDocumentAction($request);
    }

    /**
     * @Route("/get-data-by-id", name="pimcore_admin_document_link_getdatabyid", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        $link = Document\Link::getById($request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Link not found');
        }

        // check for lock
        if ($link->isAllowed('save') || $link->isAllowed('publish') || $link->isAllowed('unpublish') || $link->isAllowed('delete')) {
            if (Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            Element\Editlock::lock($request->get('id'), 'document');
        }

        $link = clone $link;

        $link->setObject(null);
        $link->setLocked($link->isLocked());
        $link->setParent(null);

        $serializer = $this->get('pimcore_admin.serializer');

        $data = $serializer->serialize($link->getObjectVars(), 'json', []);
        $data = json_decode($data, true);
        $data['rawHref'] = $link->getRawHref();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $link->getScheduledTasks()
        );

        $this->addTranslationsData($link, $data);
        $this->minimizeProperties($link, $data);

        $this->preSendDataActions($data, $link);

        if ($link->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/save", name="pimcore_admin_document_link_save", methods={"POST", "PUT"})
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        $link = Document\Link::getById($request->get('id'));

        if (!$link) {
            throw $this->createNotFoundException('Link not found');
        }

        $this->setValuesToDocument($request, $link);

        $link->setModificationDate(time());
        $link->setUserModification($this->getAdminUser()->getId());

        if ('unpublish' == $request->get('task')) {
            $link->setPublished(false);
        }
        if ('publish' == $request->get('task')) {
            $link->setPublished(true);
        }

        $task = $request->get('task');
        // only save when publish or unpublish
        if (('publish' == $task && $link->isAllowed('publish'))
            || ('unpublish' == $task && $link->isAllowed('unpublish'))
            || 'scheduler' == $task && $link->isAllowed('settings')
        ) {
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
     * @param Document\Link $link
     */
    protected function setValuesToDocument(Request $request, Document $link)
    {
        // data
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            $path = $data['path'];

            if (!empty($path)) {
                $target = null;
                if ('internal' == $data['linktype'] && $data['internalType']) {
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

            $link->setValues($data);
        }

        $this->addPropertiesToDocument($request, $link);
        $this->applySchedulerDataToElement($request, $link);
    }
}
