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

namespace Pimcore\Model\GridConfigFavourite;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\GridConfigFavourite $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param $ownerId
     * @param $classId
     *
     * @throws \Exception
     */
    public function getByOwnerAndClassId($ownerId, $classId)
    {
        $data = $this->db->fetchRow('SELECT * FROM gridconfig_favourites WHERE ownerId = ? AND classId = ?', [$ownerId, $classId]);

        if (!$data) {
            throw new \Exception('gridconfig favourite with ownerId ' . $ownerId . ' and class id ' . $classId . ' not found');
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
        $gridConfigFavourite = get_object_vars($this->model);

        foreach ($gridConfigFavourite as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('gridconfig_favourites'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate('gridconfig_favourites', $data);

        return $this->model;
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('gridconfig_favourites', ['ownerId' => $this->model->getOwnerId(), 'classId' => $this->model->getClassId()]);
    }
}
