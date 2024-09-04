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

namespace Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property Classificationstore\CollectionGroupRelation $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public const TABLE_NAME_RELATIONS = 'classificationstore_collectionrelations';

    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $colId, int $groupId): void
    {
        $this->model->setColId($colId);
        $this->model->setGroupId($groupId);

        $data = $this->db->fetchAssociative(
            sprintf(
                'SELECT * FROM %1$s LEFT JOIN `%2$s` ON `%1$s`.`colId` = `%2$s`.`id` WHERE `%1$s`.`colId` = ? AND `%1$s`.`groupId` = ?',
                self::TABLE_NAME_RELATIONS,
                Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS
            ),
            [$this->model->getColId(), $this->model->getGroupId()]
        );

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException(sprintf(
                'KeyGroupRelation with colId: %s and groupId: %s does not exist',
                $this->model->getColId(),
                $this->model->getGroupId()
            ));
        }
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME_RELATIONS, [
            'colId' => $this->model->getColId(),
            'groupId' => $this->model->getGroupId(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $type = $this->model->getObjectVars();
        $validTableColumns = $this->getValidTableColumns(self::TABLE_NAME_RELATIONS);
        $data = [];

        foreach ($type as $key => $value) {
            if (in_array($key, $validTableColumns)) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                }

                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, self::TABLE_NAME_RELATIONS, $data, $this->getPrimaryKey(self::TABLE_NAME_RELATIONS));
    }
}
