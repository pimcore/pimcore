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

namespace Pimcore\Bundle\AdminBundle\Model\GridConfigShare;

use Pimcore\Bundle\AdminBundle\Model\GridConfigShare;
use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property GridConfigShare $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int $gridConfigId
     * @param int $sharedWithUserId
     *
     * @throws Model\Exception\NotFoundException|\Doctrine\DBAL\Exception
     */
    public function getByGridConfigAndSharedWithId(int $gridConfigId, int $sharedWithUserId): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM gridconfig_shares WHERE gridConfigId = ? AND sharedWithUserId = ?', [$gridConfigId, $sharedWithUserId]);

        if (!$data) {
            throw new Model\Exception\NotFoundException('gridconfig share with gridConfigId ' . $gridConfigId . ' and shared with ' . $sharedWithUserId . ' not found');
        }

        $this->assignVariablesToModel($data);
    }

    public function save(): void
    {
        $gridConfigFavourite = $this->model->getObjectVars();
        $data = [];

        foreach ($gridConfigFavourite as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('gridconfig_shares'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, 'gridconfig_shares', $data, $this->getPrimaryKey('gridconfig_shares'));
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('gridconfig_shares', ['gridConfigId' => $this->model->getGridConfigId(), 'sharedWithUserId' => $this->model->getSharedWithUserId()]);
    }
}
