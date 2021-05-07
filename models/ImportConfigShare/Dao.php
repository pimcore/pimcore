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

namespace Pimcore\Model\ImportConfigShare;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\ImportConfigShare $model
 *
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int $importConfigId
     * @param int $sharedWithUserId
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

    public function save()
    {
        $importConfigShare = $this->model->getObjectVars();
        $data = [];

        foreach ($importConfigShare as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('importconfig_shares'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate('importconfig_shares', $data);
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('importconfig_shares', ['importConfigId' => $this->model->getImportConfigId(), 'sharedWithUserId' => $this->model->getSharedWithUserId()]);
    }
}
