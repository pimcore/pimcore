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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\WebsiteSetting $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('website-settings');
    }

    /**
     * @param int|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->getById($this->model->getId());

        if (!empty($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('Website Setting with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     * @param string|null $name
     * @param int|null $siteId
     * @param string|null $language
     *
     * @throws \Exception
     */
    public function getByName($name = null, $siteId = null, $language = null)
    {
        $data = $this->db->fetchAll(function ($row) use ($name, $siteId, $language) {
            $return = true;
            if ($name && $row['name'] != $name) {
                $return = false;
            }
            if ($row['siteId'] && $siteId && $row['siteId'] != $siteId) {
                $return = false;
            }

            if ($row['language'] != $language) {
                $return = false;
            }

            return $return;
        });

        if (count($data) > 0) {
            usort($data, function ($a, $b) {
                $result = $a['siteId'] < $b['siteId'] ? 1 : -1;

                return $result;
            });
        }

        if (count($data) && $data[0]['id']) {
            $this->assignVariablesToModel($data[0]);
        } else {
            throw new \Exception(sprintf(
                'Website Setting "%s" does not exist.',
                $this->model->getName() ?? $name
            ));
        }
    }

    /**
     * Save object to database
     */
    public function save()
    {
        $ts = time();
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];

        foreach ($dataRaw as $key => $value) {
            $data[$key] = $value;
        }

        $this->db->insertOrUpdate($data, $this->model->getId());

        if (!$this->model->getId()) {
            $this->model->setId($this->db->getLastInsertId());
        }

        $this->model->clearDependentCache();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete($this->model->getId());

        $this->model->clearDependentCache();
    }
}
