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
 * @package    Document
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Email\Log;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @property \Pimcore\Model\Tool\Email\Log $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * Name of the db table
     * @var string
     */
    protected static $dbTable = 'email_log';

     /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param integer $id
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM email_log WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
    }

     /**
     * Save document to database
     */
    public function save()
    {
        $data = [];

        $emailLog = get_object_vars($this->model);

        foreach ($emailLog as $key => $value) {
            if (in_array($key, $this->getValidTableColumns(self::$dbTable))) {

                // check if the getter exists
                $getter = "get" . ucfirst($key);
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

                $data[$key] = $value;
            }
        }

        try {
            $this->db->update(self::$dbTable, $data, ["id" => $this->model->getId()]);
        } catch (\Exception $e) {
            Logger::emerg('Could not Save emailLog with the id "'.$this->model->getId().'" ');
        }
    }


    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete(self::$dbTable, ["id" => $this->model->getId()]);
    }

    /**
     * just an alias for $this->save();
     */
    public function update()
    {
        $this->save();
    }

    /**
     * @throws \Exception
     */
    public function create()
    {
        try {
            $this->db->insert(self::$dbTable, []);

            $date = time();
            $this->model->setId($this->db->lastInsertId());
            $this->model->setCreationDate($date);
            $this->model->setModificationDate($date);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $data
     * @return array|string
     */
    protected function createJsonLoggingObject($data)
    {
        if (!is_array($data)) {
            return json_encode(new \stdClass());
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
     * @param $key
     * @param $value
     * @return \stdClass
     */
    protected function prepareLoggingData($key, $value)
    {
        $class = new \stdClass();
        $class->key = $key.' '; //dirty hack - key has to be a string otherwise the treeGrid won't work

        if (is_string($value) || is_int($value) || is_null($value)) {
            $class->data = ['type' => 'simple',
                'value' => $value];
        } elseif ($value instanceof \DateTimeInterface) {
            $class->data = ['type' => 'simple',
                'value' => $value->format("Y-m-d H:i")];
        } elseif (is_object($value) && method_exists($value, 'getId')) {
            $class->data = ['type' => 'object',
                'objectId' => $value->getId(),
                'objectClass' => get_class($value)];
        } elseif (is_array($value)) {
            foreach ($value as $entryKey => $entryValue) {
                $class->children[] = self::prepareLoggingData($entryKey, $entryValue);
            }
        }

        return $class;
    }
}
