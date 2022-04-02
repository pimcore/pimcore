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

namespace Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property Classificationstore\KeyGroupRelation $model
 */
class Dao extends AbstractDao
{
    const TABLE_NAME_RELATIONS = 'classificationstore_relations';

    /**
     * @param int|null $keyId
     * @param int|null $groupId
     */
    public function getById($keyId = null, $groupId = null)
    {
        if ($keyId != null) {
            $this->model->setKeyId($keyId);
        }

        if ($groupId != null) {
            $this->model->setGroupId($groupId);
        }

        $data = $this->db->fetchRow(
            sprintf(
                'SELECT * FROM `%1$s`, `%2$s` WHERE `%1$s`.`keyId` = `%2$s`.`id` AND `%1$s`.`keyId` = ? AND `%1$s`.`groupId` = ?',
                self::TABLE_NAME_RELATIONS,
                Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS
            ),
            [$this->model->getKeyId(), $this->model->getGroupId()]
        );

        $this->assignVariablesToModel($data);
    }

    public function save()
    {
        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete(self::TABLE_NAME_RELATIONS, [
            'keyId' => $this->model->getKeyId(),
            'groupId' => $this->model->getGroupId(),
        ]);
    }

    public function update()
    {
        $type = $this->model->getObjectVars();
        $data = [];

        foreach ($type as $key => $value) {
            if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_RELATIONS))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                }

                $data[$key] = $value;
            }
        }

        $this->db->insertOrUpdate(self::TABLE_NAME_RELATIONS, $data);
    }
}
