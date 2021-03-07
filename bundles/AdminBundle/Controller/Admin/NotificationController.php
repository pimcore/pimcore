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

declare(strict_types=1);

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Notification\Service\NotificationService;
use Pimcore\Model\Notification\Service\NotificationServiceFilterParser;
use Pimcore\Model\Notification\Service\UserService;
use Pimcore\Model\User;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/notification")
 */
class NotificationController extends AdminController
{
    /**
     * @Route("/recipients", name="pimcore_admin_notification_recipients", methods={"GET"})
     *
     * @param UserService $service
     * @param Translator $translator
     *
     * @return JsonResponse
     */
    public function recipientsAction(UserService $service, Translator $translator): JsonResponse
    {
        $this->checkPermission('notifications_send');

        $data = [];

        foreach ($service->findAll($this->getAdminUser()) as $recipient) {
            $group = $translator->trans('group');
            $prefix = $recipient->getType() == 'role' ? $group . ' - ' : '';

            $data[] = [
                'id' => $recipient->getId(),
                'text' => $prefix . $recipient->getName(),
            ];
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/send", name="pimcore_admin_notification_send", methods={"POST"})
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function sendAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications_send');

        $recipientId = (int) $request->get('recipientId', 0);
        $fromUser = (int) $this->getAdminUser()->getId();
        $title = $request->get('title', '');
        $message = $request->get('message', '');
        $elementId = $request->get('elementId');
        $elementType = $request->get('elementType', '');

        $element = Service::getElementById($elementType, $elementId);

        if (User::getById($recipientId) instanceof User) {
            $service->sendToUser($recipientId, $fromUser, $title, $message, $element);
        } else {
            $service->sendToGroup($recipientId, $fromUser, $title, $message, $element);
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/find", name="pimcore_admin_notification_find")
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function findAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications');

        $id = (int) $request->get('id', 0);
        try {
            $notification = $service->findAndMarkAsRead($id, $this->getAdminUser()->getId());
        } catch (\UnexpectedValueException $e) {
            return $this->adminJson(
                [
                    'success' => false,
                ]
            );
        }

        $data = $service->format($notification);

        return $this->adminJson([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * @Route("/find-all", name="pimcore_admin_notification_findall")
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function findAllAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications');

        $filter = ['recipient = ?' => (int) $this->getAdminUser()->getId()];
        $parser = new NotificationServiceFilterParser($request);

        foreach ($parser->parse() as $key => $val) {
            $filter[$key] = $val;
        }

        $options = [
            'offset' => $request->get('start', 0),
            'limit' => $request->get('limit', 40),
        ];

        $result = $service->findAll($filter, $options);

        $data = [];

        foreach ($result['data'] as $notification) {
            $data[] = $service->format($notification);
        }

        return $this->adminJson([
            'success' => true,
            'total' => $result['total'],
            'data' => $data,
        ]);
    }

    /**
     * @Route("/find-last-unread", name="pimcore_admin_notification_findlastunread")
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function findLastUnreadAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications');

        $user = $this->getAdminUser();
        $lastUpdate = (int) $request->get('lastUpdate', time());
        $result = $service->findLastUnread((int) $user->getId(), $lastUpdate);
        $unread = $service->countAllUnread((int) $user->getId());

        $data = [];

        foreach ($result['data'] as $notification) {
            $data[] = $service->format($notification);
        }

        return $this->adminJson([
            'success' => true,
            'total' => $result['total'],
            'data' => $data,
            'unread' => $unread,
        ]);
    }

    /**
     * @Route("/mark-as-read", name="pimcore_admin_notification_markasread")
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function markAsReadAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications');

        $id = (int) $request->get('id', 0);
        $service->findAndMarkAsRead($id, $this->getAdminUser()->getId());

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/delete", name="pimcore_admin_notification_delete")
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications');

        $id = (int) $request->get('id', 0);
        $service->delete($id, $this->getAdminUser()->getId());

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/delete-all", name="pimcore_admin_notification_deleteall")
     *
     * @param Request $request
     * @param NotificationService $service
     *
     * @return JsonResponse
     */
    public function deleteAllAction(Request $request, NotificationService $service): JsonResponse
    {
        $this->checkPermission('notifications');

        $user = $this->getAdminUser();
        $service->deleteAll((int) $user->getId());

        return $this->adminJson(['success' => true]);
    }
}
