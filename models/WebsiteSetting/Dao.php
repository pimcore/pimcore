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

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\WebsiteSetting;

/**
 * @internal
 *
 * @property WebsiteSetting $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @throws NotFoundException
     */
    public function getById(int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM website_settings WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException('Website Setting with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     * @throws NotFoundException
     */
    public function getByName(string $name = null, int $siteId = null, string $language = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }
        $data = $this->db->fetchAssociative(
            "SELECT *
            FROM website_settings
            WHERE name = ?
                AND (
                    siteId IS NULL
                    OR siteId = 0
                    OR siteId = ?
                )
                AND (
                    language IS NULL
                    OR language = ''
                    OR language = ?
                )
            ORDER BY CONCAT(siteId, language) DESC, siteId DESC, language DESC",
            [$this->model->getName(), $siteId, $language]
        );

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException('Website Setting with name: ' . $this->model->getName() . ' does not exist');
        }
    }

    public function save(): void
    {
        if ($this->model->getId()) {
            $this->update();
        } else {
            $this->create();
        }
    }

    public function delete(): void
    {
        $this->db->delete('website_settings', ['id' => $this->model->getId()]);
        $this->model->clearDependentCache();
    }

    public function update(): void
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

    public function create(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert('website_settings', ['name' => $this->model->getName(), 'siteId' => $this->model->getSiteId()]);

        $this->model->setId((int) $this->db->lastInsertId());

        $this->update();
    }
}
