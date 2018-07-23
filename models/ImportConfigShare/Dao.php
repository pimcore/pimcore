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

namespace Pimcore\Model\ImportConfigShare;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\ImportConfigShare $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param $importConfigId
     * @param $sharedWithUserId
     *
     * @throws \Exception
     */
    public function getByImportConfigAndSharedWithId($importConfigId, $sharedWithUserId)
    {
        $data = $this->db->fetchRow('SELECT * FROM importconfig_shares WHERE importConfigId = ? AND sharedWithUserId = ?', [$importConfigId, $sharedWithUserId]);

        if (!$data) {
            throw new \Exception('importconfig share with importConfigId ' . $importConfigId . ' and shared with ' . $sharedWithUserId . ' not found');
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
        $importConfigShare = get_object_vars($this->model);

        foreach ($importConfigShare as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('importconfig_shares'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate('importconfig_shares', $data);

        return $this->model;
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('importconfig_shares', ['importConfigId' => $this->model->getImportConfigId(), 'sharedWithUserId' => $this->model->getSharedWithUserId()]);
    }
}
