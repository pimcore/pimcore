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

namespace Pimcore\Model\Element\Note;

use DateTime;
use DateTimeInterface;
use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Note $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM notes WHERE id = ?', [$id]);

        if (!$data) {
            throw new Model\Exception\NotFoundException('Note item with id ' . $id . ' not found');
        }

        $data['locked'] = (bool)$data['locked'];
        $this->assignVariablesToModel($data);

        // get key-value data
        $keyValues = $this->db->fetchAllAssociative('SELECT * FROM notes_data WHERE id = ?', [$id]);
        $preparedData = [];

        foreach ($keyValues as $keyValue) {
            $data = $keyValue['data'];
            $type = $keyValue['type'];
            $name = $keyValue['name'];

            if ($type == 'document') {
                if ($data) {
                    $data = Document::getById($data);
                }
            } elseif ($type == 'asset') {
                if ($data) {
                    $data = Asset::getById($data);
                }
            } elseif ($type == 'object') {
                if ($data) {
                    $data = DataObject::getById($data);
                }
            } elseif ($type == 'date') {
                if ($data > 0) {
                    $date = new DateTime();
                    $date->setTimestamp($data);
                    $data = $date;
                }
            } elseif ($type == 'bool') {
                $data = (bool) $data;
            }

            $preparedData[$name] = [
                'data' => $data,
                'type' => $type,
            ];
        }

        $this->model->setData($preparedData);
    }

    /** Saves note to database.
     *
     * @throws Exception
     */
    public function save(): bool
    {
        $version = $this->model->getObjectVars();

        $data = [];

        // save main table
        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('notes'))) {
                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, 'notes', $data, $this->getPrimaryKey('notes'));

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId((int) $lastInsertId);
        }

        // save data table
        $this->deleteData();
        foreach ($this->model->getData() as $name => $meta) {
            $data = $meta['data'];
            $type = $meta['type'];

            if ($type == 'document') {
                if ($data instanceof Document) {
                    $data = $data->getId();
                }
            } elseif ($type == 'asset') {
                if ($data instanceof Asset) {
                    $data = $data->getId();
                }
            } elseif ($type == 'object') {
                if ($data instanceof DataObject\AbstractObject) {
                    $data = $data->getId();
                }
            } elseif ($type == 'date') {
                if ($data instanceof DateTimeInterface) {
                    $data = $data->getTimestamp();
                }
            } elseif ($type == 'bool') {
                $data = (bool) $data;
            }

            $this->db->insert('notes_data', [
                'id' => $this->model->getId(),
                'name' => $name,
                'type' => $type,
                'data' => $data,
            ]);
        }

        return true;
    }

    /** Deletes note from database.
     * @throws Exception
     */
    public function delete(): void
    {
        $this->db->delete('notes', ['id' => $this->model->getId()]);
        $this->deleteData();
    }

    /** Deletes note data from database.
     * @throws Exception
     */
    protected function deleteData(): void
    {
        $this->db->delete('notes_data', ['id' => $this->model->getId()]);
    }
}
