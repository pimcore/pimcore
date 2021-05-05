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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property \Pimcore\Model\WebsiteSetting $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int|null $id
     *
     * @throws NotFoundException
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM website_settings WHERE id = ?', $this->model->getId());
        $this->assignVariablesToModel($data);

        if (!empty($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException('Website Setting with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     * @param string|null $name
     * @param int|null $siteId
     * @param string|null $language
     *
     * @throws NotFoundException
     */
    public function getByName($name = null, $siteId = null, $language = null)
    {
        if ($name != null) {
            $this->model->setName($name);
        }
        $data = $this->db->fetchRow("SELECT * FROM website_settings WHERE name = ? AND (siteId IS NULL OR siteId = '' OR siteId = ?) AND  (language IS NULL OR language = '' OR language = ?) ORDER BY siteId,language DESC", [$this->model->getName(), $siteId, $language]);

        if (!empty($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException('Website Setting with name: ' . $this->model->getName() . ' does not exist');
        }
    }

    public function save()
    {
        if ($this->model->getId()) {
            $this->update();
        } else {
            $this->create();
        }
    }

    public function delete()
    {
        $this->db->delete('website_settings', ['id' => $this->model->getId()]);
        $this->model->clearDependentCache();
    }

    public function update()
    {
        $ts = time();
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('website_settings'))) {
                $data[$key] = $value;
            }
        }

        $this->db->update('website_settings', $data, ['id' => $this->model->getId()]);

        $this->model->clearDependentCache();
    }

    public function create()
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert('website_settings', ['name' => $this->model->getName(), 'siteId' => $this->model->getSiteId()]);

        $this->model->setId($this->db->lastInsertId());

        $this->update();
    }
}
