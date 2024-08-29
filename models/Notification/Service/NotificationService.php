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

namespace Pimcore\Model\Notification\Service;

use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Pimcore\Db;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\Notification\Listing;
use Pimcore\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use UnexpectedValueException;

/**
 * @internal
 * With the end of Classic-UI this service will be deprecated.
 * Functionality will then be moved from studio to the core again
 */
class NotificationService
{
    private UserService $userService;

    /**
     * NotificationService constructor.
     *
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     *
     * @throws UnexpectedValueException
     * @throws Exception
     */
    public function sendToUser(
        int $userId,
        int $fromUser,
        string $title,
        string $message,
        ?ElementInterface $element = null
    ): void {
        $this->beginTransaction();

        $sender = User::getById($fromUser);
        $recipient = User::getById($userId);

        if (!$recipient instanceof User) {
            throw new UnexpectedValueException(sprintf('No user found with the ID %d', $userId));
        }

        if (empty($title)) {
            throw new UnexpectedValueException('Title of the Notification cannot be empty');
        }

        if (empty($message)) {
            throw new UnexpectedValueException('Message text of the Notification cannot be empty');
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
     * @throws UnexpectedValueException|Exception
     */
    public function sendToGroup(
        int $groupId,
        int $fromUser,
        string $title,
        string $message,
        ?ElementInterface $element = null
    ): void {
        $group = User\Role::getById($groupId);

        if (!$group instanceof User\Role) {
            throw new UnexpectedValueException(sprintf('No group found with the ID %d', $groupId));
        }

        $listing = new User\Listing();
        $listing->setCondition(
            'id != ?
            AND active = ?
            AND (
                roles = ?
                OR roles LIKE ?
                OR roles LIKE ?
                OR roles LIKE ?
            )',
            [
                $fromUser,
                1,
                $groupId,
                '%,' . $groupId,
                $groupId . ',%',
                '%,' . $groupId . ',%',
            ]
        );
        $listing->setOrderKey('name');
        $listing->setOrder('ASC');
        $listing->load();

        $users = $listing->getUsers();
        $users = $this->userService->filterUsersWithPermission($users);

        foreach ($users as $user) {
            $this->sendToUser($user->getId(), $fromUser, $title, $message, $element);
        }
    }

    /**
     * @throws UnexpectedValueException
     */
    public function find(int $id): Notification
    {
        $notification = Notification::getById($id);

        if (!$notification instanceof Notification) {
            throw new UnexpectedValueException("Notification with the ID {$id} doesn't exists");
        }

        return $notification;
    }

    /**
     *
     *
     * @throws UnexpectedValueException
     * @throws Exception
     */
    public function findAndMarkAsRead(int $id, ?int $recipientId = null): Notification
    {
        $this->beginTransaction();
        $notification = $this->find($id);

        if ($notification->getRecipient()?->getId() !== $recipientId) {
            throw new AccessDeniedHttpException();
        }

        if ($recipientId && $recipientId === $notification->getRecipient()?->getId()) {
            $notification->setRead(true);
            $notification->save();
            $this->commit();
        }

        return $notification;
    }

    /**
     * @param array<string, mixed> $filter
     * @param array{offset?: int|string, limit?: int|string|null} $options
     *
     * @return array{total: int, data: Notification[]}
     *
     * @throws Exception
     */
    public function findAll(array $filter = [], array $options = []): array
    {
        $listing = new Listing();

        $filter  = [...$filter, ...['isStudio' => 0]];

        $conditions = [];
        $conditionVariables = [];
        foreach ($filter as $key => $value) {
            if (isset($value['condition'])) {
                $conditions[] = $value['condition'];
                $conditionVariables[] = $value['conditionVariables'] ?? [];
            } else {
                $conditions[] = $key . ' = :' . $key;
                $conditionVariables[] = [$key => $value];
            }
        }

        $condition = implode(' AND ', $conditions);
        $listing->setCondition($condition, array_merge(...$conditionVariables));

        $listing->setOrderKey('creationDate');
        $listing->setOrder('DESC');
        $offset = $options['offset'] ?? 0;
        $limit = $options['limit'] ?? null;

        if (is_string($offset)) {
            //TODO: Trigger deprecation
            $offset = (int) $offset;
        }
        if (is_string($limit)) {
            //TODO: Trigger deprecation
            $limit = (int) $limit;
        }

        $this->beginTransaction();

        $result = [
            'total' => $listing->count(),
            'data' => $listing->getItems($offset, $limit),
        ];

        $this->commit();

        return $result;
    }

    /**
     * @throws Exception
     */
    public function findLastUnread(int $user, int $lastUpdate): array
    {
        $listing = new Listing();
        $listing->setCondition(
            'recipient = ? AND `read` = 0 AND `isStudio` = 0 AND creationDate >= ?',
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

    public function format(Notification $notification): array
    {
        $carbonTs = new Carbon($notification->getCreationDate(), 'UTC');
        $data = [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'sender' => '',
            'read' => (int) $notification->isRead(),
            'date' => $notification->getCreationDate(),
            'timestamp' => $carbonTs->getTimestamp(),
            'linkedElementType' => $notification->getLinkedElementType(),
            'linkedElementId' => null,
        ];

        if ($notification->getLinkedElement()) {
            $data['linkedElementId'] = $notification->getLinkedElement()->getId();
        }

        $sender = $notification->getSender();

        if ($sender instanceof User\AbstractUser) {
            $from = trim(sprintf('%s %s', $sender->getFirstname(), $sender->getLastname()));

            if ($from === '') {
                $from = $sender->getName();
            }

            $data['sender'] = $from;
        }

        return $data;
    }

    public function countAllUnread(int $user): int
    {
        $listing = new Listing();
        $listing->setCondition('recipient = ? AND `read` = 0 AND `isStudio` = 0', [$user]);

        return $listing->count();
    }

    /**
     * @throws Exception
     */
    public function delete(int $id, ?int $recipientId = null): void
    {
        $this->beginTransaction();

        $notification = $this->find($id);

        if ($recipientId && $recipientId === $notification->getRecipient()?->getId()) {
            $notification->delete();
        }

        $this->commit();
    }

    /**
     * @throws Exception
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

    /**
     * @throws Exception
     */
    private function beginTransaction(): void
    {
        Db::getConnection()->beginTransaction();
    }

    /**
     * @throws Exception
     */
    private function commit(): void
    {
        Db::getConnection()->commit();
    }
}
