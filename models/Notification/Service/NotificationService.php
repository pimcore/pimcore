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

namespace Pimcore\Model\Notification\Service;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Listing;
use Pimcore\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NotificationService
{
    /** @var UserService */
    private $userService;

    /**
     * NotificationService constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param int $userId
     * @param int $fromUser
     * @param string $title
     * @param string $message
     * @param ElementInterface|null $element
     *
     * @throws \UnexpectedValueException
     */
    public function sendToUser(
        int $userId,
        int $fromUser,
        string $title,
        string $message,
        ?ElementInterface $element = null
    ) {
        $this->beginTransaction();

        $sender = User::getById($fromUser);
        $recipient = User::getById($userId);

        if (!$recipient instanceof User) {
            throw new \UnexpectedValueException(sprintf('No user found with the ID %d', $userId));
        }

        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setSender($sender);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setLinkedElement($element);
        $notification->save();

        $this->commit();
    }

    /**
     * @param int $groupId
     * @param int $fromUser
     * @param string $title
     * @param string $message
     * @param ElementInterface|null $element
     *
     * @throws \UnexpectedValueException
     */
    public function sendToGroup(
        int $groupId,
        int $fromUser,
        string $title,
        string $message,
        ?ElementInterface $element = null
    ) {
        $group = User\Role::getById($groupId);

        if (!$group instanceof User\Role) {
            throw new \UnexpectedValueException(sprintf('No group found with the ID %d', $groupId));
        }

        $filter = [
            'id != ?' => $fromUser,
            'active = ?' => 1,
            'roles LIKE ?' => '%' . $groupId . '%',
        ];

        $condition = implode(' AND ', array_keys($filter));
        $conditionVariables = array_values($filter);

        $listing = new User\Listing();
        $listing->setCondition($condition, $conditionVariables);
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');
        $listing->load();

        $users = $listing->getUsers() ?? [];
        $users = $this->userService->filterUsersWithPermission($users);

        foreach ($users as $user) {
            $this->sendToUser($user->getId(), $fromUser, $title, $message, $element);
        }
    }

    /**
     * @param int $id
     *
     * @return Notification
     *
     * @throws \UnexpectedValueException
     */
    public function find(int $id): Notification
    {
        $notification = Notification::getById($id);

        if (!$notification instanceof Notification) {
            throw new \UnexpectedValueException("Notification with the ID {$id} doesn't exists");
        }

        return $notification;
    }

    /**
     * @param int $id
     * @param int|null $recipientId
     *
     * @return Notification
     *
     * @throws \UnexpectedValueException
     */
    public function findAndMarkAsRead(int $id, ?int $recipientId = null): Notification
    {
        $this->beginTransaction();
        $notification = $this->find($id);

        if ($notification->getRecipient()->getId() != $recipientId) {
            throw new AccessDeniedHttpException();
        }

        if ($recipientId && $recipientId == $notification->getRecipient()->getId()) {
            $notification->setRead(true);
            $notification->save();
            $this->commit();
        }

        return $notification;
    }

    /**
     * @param array $filter
     * @param array $options
     *
     * @return array
     */
    public function findAll(array $filter = [], array $options = []): array
    {
        $listing = new Listing();

        if (!empty($filter)) {
            $condition = implode(' AND ', array_keys($filter));
            $conditionVariables = array_values($filter);
            $listing->setCondition($condition, $conditionVariables);
        }

        $listing->setOrderKey('creationDate');
        $listing->setOrder('DESC');
        $options += ['offset' => 0, 'limit' => 0];
        $offset = (int) $options['offset'];
        $limit = (int) $options['limit'];

        $this->beginTransaction();

        $result = [
            'total' => $listing->count(),
            'data' => $listing->getItems($offset, $limit),
        ];

        $this->commit();

        return $result;
    }

    /**
     * @param int $user
     * @param int $lastUpdate
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findLastUnread(int $user, int $lastUpdate): array
    {
        $listing = new Listing();
        $listing->setCondition(
            'recipient = ? AND `read` = 0 AND creationDate >= ?',
            [
                $user,
                date('Y-m-d H:i:s', $lastUpdate),
            ]
        );
        $listing->setOrderKey('creationDate');
        $listing->setOrder('DESC');
        $listing->setLimit(1);

        $this->beginTransaction();

        $result = [
            'total' => $listing->count(),
            'data' => $listing->getData(),
        ];

        $this->commit();

        return $result;
    }

    /**
     * @param Notification $notification
     *
     * @return array
     */
    public function format(Notification $notification): array
    {
        $data = [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'sender' => '',
            'read' => (int) $notification->isRead(),
            'date' => $notification->getCreationDate(),
            'linkedElementType' => $notification->getLinkedElementType(),
            'linkedElementId' => null,
        ];

        if ($notification->getLinkedElement()) {
            $data['linkedElementId'] = $notification->getLinkedElement()->getId();
        }

        $sender = $notification->getSender();

        if ($sender instanceof User\AbstractUser) {
            $from = trim(sprintf('%s %s', $sender->getFirstname(), $sender->getLastname()));

            if (strlen($from) === 0) {
                $from = $sender->getName();
            }

            $data['sender'] = $from;
        }

        return $data;
    }

    /**
     * @param int $user
     *
     * @return int
     */
    public function countAllUnread(int $user): int
    {
        $listing = new Listing();
        $listing->setCondition('recipient = ? AND `read` = 0', [$user]);

        return $listing->count();
    }

    /**
     * @param int $id
     * @param int|null $recipientId
     */
    public function delete(int $id, ?int $recipientId = null): void
    {
        $this->beginTransaction();

        $notification = $this->find($id);

        if ($recipientId && $recipientId == $notification->getRecipient()->getId()) {
            $notification->delete();
        }

        $this->commit();
    }

    /**
     * @param int $user
     */
    public function deleteAll(int $user): void
    {
        $listing = new Listing();
        $listing->setCondition('recipient = ?', [$user]);

        $this->beginTransaction();

        foreach ($listing->getData() as $notification) {
            $notification->delete();
        }

        $this->commit();
    }

    private function beginTransaction(): void
    {
        \Pimcore\Db::getConnection()->beginTransaction();
    }

    private function commit(): void
    {
        \Pimcore\Db::getConnection()->commit();
    }
}
