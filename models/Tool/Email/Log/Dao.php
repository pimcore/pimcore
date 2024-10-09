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

namespace Pimcore\Model\Tool\Email\Log;

use DateTimeInterface;
use Exception;
use Pimcore\Logger;
use Pimcore\Model;
use stdClass;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\Email\Log $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Name of the db table
     *
     */
    protected static string $dbTable = 'email_log';

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM email_log WHERE id = ?', [$this->model->getId()]);
        if (!$data) {
            throw new Model\Exception\NotFoundException('email log with id ' . $id . ' not found');
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save document to database
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $data = [];

        $emailLog = $this->model->getObjectVars();

        foreach ($emailLog as $key => $value) {
            if (in_array($key, $this->getValidTableColumns(self::$dbTable))) {
                // check if the getter exists
                $getter = 'get' . ucfirst($key);
                if (!method_exists($this->model, $getter)) {
                    continue;
                }

                // get the value from the getter
                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int) $value;
                } elseif (is_array($value)) {
                    //converts the dynamic params to a basic json string
                    $preparedData = self::createJsonLoggingObject($value);
                    $value = json_encode($preparedData);
                }

                $key = $this->db->quoteIdentifier($key);
                $data[$key] = $value;
            }
        }

        try {
            $this->db->update(self::$dbTable, $data, ['id' => $this->model->getId()]);
        } catch (Exception $e) {
            Logger::emerg('Could not Save emailLog with the id "'.$this->model->getId().'" ');
        }
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete(self::$dbTable, ['id' => $this->model->getId()]);
    }

    public function create(): void
    {
        $this->db->insert(self::$dbTable, []);

        $date = time();
        $this->model->setId((int) $this->db->lastInsertId());
        $this->model->setModificationDate($date);
    }

    protected function createJsonLoggingObject(array|string $data): array|string
    {
        if (!is_array($data)) {
            return json_encode(new stdClass());
        } else {
            $loggingData = [];
            foreach ($data as $key => $value) {
                $loggingData[] = self::prepareLoggingData($key, $value);
            }

            return $loggingData;
        }
    }

    /**
     * Creates the basic logging for the treeGrid in the backend
     * Data will be enhanced with live-data in the backend
     *
     *
     */
    protected function prepareLoggingData(string $key, mixed $value): stdClass
    {
        $class = new stdClass();
        $class->key = $key; // key has to be a string otherwise the treeGrid won't work

        if (is_string($value) || is_int($value) || is_null($value)) {
            $class->data = ['type' => 'simple',
                'value' => $value, ];
        } elseif ($value instanceof DateTimeInterface) {
            $class->data = ['type' => 'simple',
                'value' => $value->format('Y-m-d H:i'), ];
        } elseif (is_object($value) && method_exists($value, 'getId')) {
            $class->data = ['type' => 'object',
                'objectId' => $value->getId(),
                'objectClass' => get_class($value), ];
        } elseif (is_array($value)) {
            foreach ($value as $entryKey => $entryValue) {
                $class->children[] = self::prepareLoggingData($entryKey, $entryValue);
            }
        }

        return $class;
    }
}
