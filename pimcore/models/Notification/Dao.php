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
 * @category   Pimcore
 * @package    Notification
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Notification;

use Pimcore\Model;

/**
 * @author Piotr Ćwięcek <pcwiecek@divante.pl>
 * @author Kamil Karkus <kkarkus@divante.pl>
 *
 * @property \Pimcore\Model\Notification $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Fetch a row by an id from the database and assign variables to the document model.
     *
     * @param $id
     *
     * @throws \Exception
     *
     * @return void
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT notifications.* FROM notifications WHERE notifications.id = ?", $id);

        if ($data["id"] > 0) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Notification with the ID " . $id . " doesn't exists");
        }
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function create()
    {
        try {
            $this->db->insert("notifications", [
                "title" => $this->model->getTitle(),
                "message" => $this->model->getMessage(),
                "type" => $this->model->getType(),
                "fromUser" => $this->model->getFromUser(),
                "user" => $this->model->getUser(),
                "unread" => $this->model->isUnread(),
                "creationDate" => time()
            ]);

            $this->model->setId($this->db->lastInsertId());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function update()
    {
        try {
            $this->db->update("notifications", [
                "title" => $this->model->getTitle(),
                "message" => $this->model->getMessage(),
                "type" => $this->model->getType(),
                "fromUser" => $this->model->getFromUser(),
                "user" => $this->model->getUser(),
                "unread" => $this->model->isUnread(),
                "creationDate" => $this->model->getCreationDate(),
                "modificationDate" => time()
            ], $this->db->quoteInto("id = ?", $this->model->getId()));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function save()
    {
        if ($this->model->getId()) {
            $this->update();
            return;
        }
        $this->create();
    }

    /**
     * Delete the row from the database. (based on the model id)
     *
     * @throws \Exception
     *
     * @return void
     */
    public function delete()
    {
        try {
            $this->db->delete("notifications", $this->db->quoteInto("id = ?", $this->model->getId()));
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
