<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

use Pimcore;
use Pimcore\Db\Helper;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property Classificationstore\KeyGroupRelation $model
 */
class Dao extends AbstractDao
{
    public const TABLE_NAME_RELATIONS = 'classificationstore_relations';

    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $keyId, int $groupId): void
    {
        $this->model->setKeyId($keyId);
        $this->model->setGroupId($groupId);

        $data = $this->db->fetchAssociative(
            sprintf(
                'SELECT * FROM `%1$s` LEFT JOIN `%2$s` ON `%1$s`.`keyId` = `%2$s`.`id` WHERE `%1$s`.`keyId` = ? AND `%1$s`.`groupId` = ?',
                self::TABLE_NAME_RELATIONS,
                Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS
            ),
            [$this->model->getKeyId(), $this->model->getGroupId()]
        );

        if ($data) {
            $data['enabled'] = (bool)$data['enabled'];
            $data['mandatory'] = (bool)$data['mandatory'];
            $this->assignVariablesToModel($data);
        } else {
            throw new NotFoundException(sprintf(
                'KeyGroupRelation with keyId: %s and groupId: %s does not exist',
                $this->model->getKeyId(),
                $this->model->getGroupId()
            ));
        }
    }

    public function save(): void
    {
        $this->update();
    }

    /**
     * The vocabulary is scoped to the store of the group, not of the key, so the group decides which
     * entries a relation may name.
     *
     * @throws InvalidConfigException
     */
    public function assertSubGroupIsResolvable(): void
    {
        $subGroup = $this->model->getSubGroup();
        if ($subGroup === null) {
            return;
        }

        $resolved = $this->db->fetchOne(
            'SELECT `s`.`id` FROM `' . Classificationstore\SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS . '` `s`'
            . ' INNER JOIN `' . Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '` `g`'
            . ' ON `g`.`storeId` = `s`.`storeId`'
            . ' WHERE `g`.`id` = ? AND `s`.`identifier` = ?',
            [$this->model->getGroupId(), $subGroup]
        );

        if ($resolved === false) {
            throw new InvalidConfigException(sprintf(
                'The sub group "%s" does not exist in the store of group %s.',
                $subGroup,
                $this->model->getGroupId()
            ));
        }
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME_RELATIONS, [
            'keyId' => $this->model->getKeyId(),
            'groupId' => $this->model->getGroupId(),
        ]);

        $this->clearRelationCache();
    }

    public function update(): void
    {
        $type = $this->model->getObjectVars();
        $validTableColumns = $this->getValidTableColumns(self::TABLE_NAME_RELATIONS);
        $data = [];

        foreach ($type as $key => $value) {
            // a relation built with `new` carries no sub group, and must not clear a stored tag
            if ($key === 'subGroup' && !$this->model->isSubGroupInitialized()) {
                continue;
            }

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

        $this->clearRelationCache();
    }

    /**
     * A relation dispatches no event, so the process-scoped relation cache is invalidated here -
     * which covers every caller that goes through the model.
     */
    private function clearRelationCache(): void
    {
        Pimcore::getContainer()
            ->get(Classificationstore\KeyGroupRelationCache::class)
            ->clearGroup($this->model->getGroupId());
    }
}
