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
 * @package    Version
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\ImportConfig;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\ImportConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getById($id)
    {
        $data = $this->db->fetchRow('SELECT * FROM importconfigs WHERE id = ?', $id);

        if (!$data['id']) {
            throw new \Exception('importconfig with id ' . $id . ' not found');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return int
     */
    public function save()
    {
        $importconfigs = get_object_vars($this->model);

        foreach ($importconfigs as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('importconfigs'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate('importconfigs', $data);

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId($lastInsertId);
        }

        return $this->model->getId();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('importconfigs', ['id' => $this->model->getId()]);
    }
}
