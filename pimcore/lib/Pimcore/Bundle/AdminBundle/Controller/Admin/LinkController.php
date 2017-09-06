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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/link")
 */
class LinkController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {

        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'document')) {
            return $this->json([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'document')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'document');

        $link = Document\Link::getById($request->get('id'));
        $link = clone $link;

        $link->setObject(null);
        $link->idPath = Element\Service::getIdPath($link);
        $link->userPermissions = $link->getUserPermissions();
        $link->setLocked($link->isLocked());
        $link->setParent(null);
        $this->addTranslationsData($link);
        $this->minimizeProperties($link);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $serializer = $this->get('pimcore_admin.serializer');

        $data = $serializer->serialize($link, 'json', [
        ]);
        $data = json_decode($data, true);
        $data['rawHref'] = $link->getRawHref();

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $link
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($link->isAllowed('view')) {
            return $this->json($data);
        }

        return $this->json(false);
    }

    /**
     * @Route("/save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get('id')) {
                $link = Document\Link::getById($request->get('id'));
                $this->setValuesToDocument($request, $link);

                $link->setModificationDate(time());
                $link->setUserModification($this->getUser()->getId());

                if ($request->get('task') == 'unpublish') {
                    $link->setPublished(false);
                }
                if ($request->get('task') == 'publish') {
                    $link->setPublished(true);
                }

                // only save when publish or unpublish
                if (($request->get('task') == 'publish' && $link->isAllowed('publish')) || ($request->get('task') == 'unpublish' && $link->isAllowed('unpublish'))) {
                    $link->save();

                    return $this->json(['success' => true]);
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                return $this->json(['success' => false, 'type' => 'ValidationException', 'message' => $e->getMessage(), 'stack' => $e->getTraceAsString(), 'code' => $e->getCode()]);
            }
            throw $e;
        }

        return $this->json(false);
    }

    /**
     * @param Request $request
     * @param Document\Link $link
     */
    protected function setValuesToDocument(Request $request, Document $link)
    {

        // data
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            $path = $data['path'];

            if (!empty($path)) {
                if ($data["linktype"] == "internal" && $data["internalType"]) {
                    $target = Element\Service::getElementByPath($data["internalType"], $path);
                    if ($target) {
                        $data['internal'] = true;
                        $data['internal'] = $target->getId();
                        $data['internalType'] = $data["internalType"];
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
    }
}
