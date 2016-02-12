<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Element\Note;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;

class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param $id
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow("SELECT * FROM notes WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new \Exception("Note item with id " . $id . " not found");
        }
        $this->assignVariablesToModel($data);

        // get key-value data
        $keyValues = $this->db->fetchAll("SELECT * FROM notes_data WHERE id = ?", $id);
        $preparedData = array();

        foreach ($keyValues as $keyValue) {
            $data = $keyValue["data"];
            $type = $keyValue["type"];
            $name = $keyValue["name"];

            if ($type == "document") {
                if ($data) {
                    $data = Document::getById($data);
                }
            } elseif ($type == "asset") {
                if ($data) {
                    $data = Asset::getById($data);
                }
            } elseif ($type == "object") {
                if ($data) {
                    $data = Object\AbstractObject::getById($data);
                }
            } elseif ($type == "date") {
                if ($data > 0) {
                    $date = new \DateTime();
                    $date->setTimestamp($data);
                    $data = $date;
                }
            } elseif ($type == "bool") {
                $data = (bool) $data;
            }

            $preparedData[$name] = array(
                "data" => $data,
                "type" => $type
            );
        }

        $this->model->setData($preparedData);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save()
    {
        $version = get_object_vars($this->model);

        // save main table
        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("notes"))) {
                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate("notes", $data);

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId($lastInsertId);
        }

        // save data table
        $this->deleteData();
        foreach ($this->model->getData() as $name => $meta) {
            $data = $meta["data"];
            $type = $meta["type"];

            if ($type == "document") {
                if ($data instanceof Document) {
                    $data = $data->getId();
                }
            } elseif ($type == "asset") {
                if ($data instanceof Asset) {
                    $data = $data->getId();
                }
            } elseif ($type == "object") {
                if ($data instanceof Object\AbstractObject) {
                    $data = $data->getId();
                }
            } elseif ($type == "date") {
                if ($data instanceof \DateTime) {
                    $data = $data->getTimestamp();
                }
            } elseif ($type == "bool") {
                $data = (bool) $data;
            }

            $this->db->insert("notes_data", array(
                "id" => $this->model->getId(),
                "name" => $name,
                "type" => $type,
                "data" => $data
            ));
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->delete("notes", $this->db->quoteInto("id = ?", $this->model->getId()));
        $this->deleteData();
    }

    protected function deleteData()
    {
        $this->db->delete("notes_data", $this->db->quoteInto("id = ?", $this->model->getId()));
    }
}
