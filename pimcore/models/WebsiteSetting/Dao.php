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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\WebsiteSetting $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM website_settings WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
        
        if ($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Website Setting with id: " . $this->model->getId() . " does not exist");
        }
    }

    /**
     * @param null $name
     * @param null $siteId
     * @throws \Exception
     */
    public function getByName($name = null, $siteId = null)
    {
        if ($name != null) {
            $this->model->setName($name);
        }
        $data = $this->db->fetchRow("SELECT * FROM website_settings WHERE name = ? AND (siteId IS NULL OR siteId = '' OR siteId = ?) ORDER BY siteId DESC", [$this->model->getName(), $siteId]);
        
        if (!empty($data["id"])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Website Setting with name: " . $this->model->getName() . " does not exist");
        }
    }

    /**
     * Save object to database
     *
     * @return boolean
     *
     * @todo: create and update don't return anything
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->model->update();
        }

        return $this->create();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete("website_settings", ["id" => $this->model->getId()]);
        
        $this->model->clearDependentCache();
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        try {
            $ts = time();
            $this->model->setModificationDate($ts);

            $type = get_object_vars($this->model);
            $data = [];

            foreach ($type as $key => $value) {
                if (in_array($key, $this->getValidTableColumns("website_settings"))) {
                    $data[$key] = $value;
                }
            }

            $this->db->update("website_settings", $data, ["id" => $this->model->getId()]);
        } catch (\Exception $e) {
            throw $e;
        }
        
        $this->model->clearDependentCache();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create()
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert("website_settings", ["name" => $this->model->getName(), "siteId" => $this->model->getSiteId()]);

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
