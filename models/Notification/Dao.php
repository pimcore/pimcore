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

use Pimcore\Db\Helper;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Element;
use Pimcore\Model\Exception\NotFoundException;

use Pimcore\Model\Notification;
use Pimcore\Model\User;

/**
 * @internal
 *
 * @property \Pimcore\Model\Notification $model
 */
class Dao extends AbstractDao
{
    const DB_TABLE_NAME = 'notifications';

    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE id = ?', static::DB_TABLE_NAME);
        $data = $this->db->fetchAssociative($sql, [$id]);

        if ($data === false) {
            $message = sprintf('Notification with id %d not found', $id);

            throw new NotFoundException($message);
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save notification
     */
    public function save(): void
    {
        $model = $this->getModel();
        $model->setModificationDate(date('Y-m-d H:i:s'));

        if ($model->getId() === null) {
            $model->setCreationDate($model->getModificationDate());
        }

        Helper::upsert($this->db, static::DB_TABLE_NAME, $this->getData($model), $this->getPrimaryKey(static::DB_TABLE_NAME));

        if ($model->getId() === null) {
            $model->setId((int) $this->db->lastInsertId());
        }
    }

    /**
     * Delete notification
     */
    public function delete(): void
    {
        $this->db->delete(static::DB_TABLE_NAME, [
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
            throw new \UnexpectedValueException(sprintf('No user found with the ID %d', $data['recipient']));
        }

        if (empty($data['title'])) {
            throw new \UnexpectedValueException('Title of the Notification cannot be empty');
        }

        if (empty($data['message'])) {
            throw new \UnexpectedValueException('Message text of the Notification cannot be empty');
        }

        $linkedElement = null;

        if ($data['linkedElement']) {
            $linkedElement = Element\Service::getElementById($data['linkedElementType'], $data['linkedElement']);
        }

        $model->setId((int)$data['id']);
        $model->setCreationDate($data['creationDate']);
        $model->setModificationDate($data['modificationDate']);
        $model->setSender($sender);
        $model->setRecipient($recipient);
        $model->setTitle($data['title']);
        $model->setType($data['type']);
        $model->setMessage($data['message']);
        $model->setLinkedElement($linkedElement);
        $model->setRead($data['read'] == 1 ? true : false);
    }

    protected function getData(Notification $model): array
    {
        return [
            'id' => $model->getId(),
            'creationDate' => $model->getCreationDate(),
            'modificationDate' => $model->getModificationDate(),
            'sender' => $model->getSender() ? $model->getSender()->getId() : null,
            'recipient' => $model->getRecipient()->getId(),
            'title' => $model->getTitle(),
            'message' => $model->getMessage(),
            'linkedElement' => $model->getLinkedElement() ? $model->getLinkedElement()->getId() : null,
            'linkedElementType' => $model->getLinkedElementType(),
            'read' => (int) $model->isRead(),
        ];
    }

    protected function getModel(): Notification
    {
        return $this->model;
    }
}
