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

namespace Pimcore\Model\Notification;

use Doctrine\DBAL\Exception;
use Pimcore\Db\Helper;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Notification;
use Pimcore\Model\User;
use UnexpectedValueException;

/**
 * @internal
 *
 * @property Notification $model
 */
class Dao extends AbstractDao
{
    public const DB_TABLE_NAME = 'notifications';

    /**
     *
     * @throws NotFoundException
     * @throws Exception
     */
    public function getById(int $id): void
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE id = ?', self::DB_TABLE_NAME);
        $data = $this->db->fetchAssociative($sql, [$id]);

        if ($data === false) {
            $message = sprintf('Notification with id %d not found', $id);

            throw new NotFoundException($message);
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save notification
     *
     * @throws Exception
     */
    public function save(): void
    {
        $model = $this->getModel();
        $model->setModificationDate(date('Y-m-d H:i:s'));

        if ($model->getId() === null) {
            $model->setCreationDate($model->getModificationDate());
        }

        Helper::upsert(
            $this->db,
            self::DB_TABLE_NAME,
            $this->getData($model),
            $this->getPrimaryKey(self::DB_TABLE_NAME)
        );

        if ($model->getId() === null) {
            $model->setId((int) $this->db->lastInsertId());
        }
    }

    /**
     * Delete notification
     *
     * @throws Exception
     */
    public function delete(): void
    {
        $this->db->delete(self::DB_TABLE_NAME, [
            'id' => $this->getModel()->getId(),
        ]);
    }

    protected function assignVariablesToModel(array $data): void
    {
        $model = $this->getModel();
        $sender = null;

        if ($data['sender']) {
            $user = User::getById($data['sender']);
            if ($user instanceof User) {
                $sender = $user;
            }
        }

        $recipient = null;

        if ($data['recipient']) {
            $user = User::getById($data['recipient']);
            if ($user instanceof User) {
                $recipient = $user;
            }
        }

        if (!$recipient instanceof User) {
            throw new UnexpectedValueException(sprintf('No user found with the ID %d', $data['recipient']));
        }

        if (!isset($data['title']) || !is_string($data['title']) || $data['title'] === '') {
            throw new UnexpectedValueException('Title of the Notification cannot be empty');
        }

        if (!isset($data['message']) || !is_string($data['message']) || $data['message'] === '') {
            throw new UnexpectedValueException('Message text of the Notification cannot be empty');
        }

        $linkedElement = null;

        if ($data['linkedElement']) {
            $linkedElement = Service::getElementById($data['linkedElementType'], $data['linkedElement']);
        }

        $model->setId((int)$data['id']);
        $model->setCreationDate($data['creationDate']);
        $model->setModificationDate($data['modificationDate']);
        $model->setSender($sender);
        $model->setRecipient($recipient);
        $model->setTitle($data['title']);
        $model->setType($data['type'] ?? 'info');
        $model->setMessage($data['message']);
        $model->setLinkedElement($linkedElement);
        $model->setRead($data['read'] === 1);
        $model->setPayload($data['payload']);
        $model->setIsStudio($data['isStudio'] === 1); // TODO: Remove with end of Classic-UI
    }

    protected function getData(Notification $model): array
    {
        return [
            'id' => $model->getId(),
            'creationDate' => $model->getCreationDate(),
            'type' => $model->getType() ?? 'info',
            'modificationDate' => $model->getModificationDate(),
            'sender' => $model->getSender()?->getId(),
            'recipient' => $model->getRecipient()?->getId(),
            'title' => $model->getTitle(),
            'message' => $model->getMessage(),
            'linkedElement' => $model->getLinkedElement()?->getId(),
            'linkedElementType' => $model->getLinkedElementType(),
            'read' => (int) $model->isRead(),
            'payload' => $model->getPayload(),
            'isStudio' => (int) $model->isStudio(), // TODO: Remove with end of Classic-UI
        ];
    }

    protected function getModel(): Notification
    {
        return $this->model;
    }
}
