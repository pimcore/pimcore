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
use Pimcore\Model\DataObject\Classificationstore\Traits\AtomicWriteTrait;
use Pimcore\Model\DataObject\Classificationstore\Traits\PerAssignmentDaoTrait;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Tool\Serialize;
use Throwable;

/**
 * @internal
 *
 * @property Classificationstore\KeyGroupRelation $model
 */
class Dao extends AbstractDao
{
    use AtomicWriteTrait;
    use PerAssignmentDaoTrait;

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
            $this->assignVariablesToModel($this->hydratePerAssignmentColumns($this->model, $data));
        } else {
            throw new NotFoundException(sprintf(
                'KeyGroupRelation with keyId: %s and groupId: %s does not exist',
                $this->model->getKeyId(),
                $this->model->getGroupId()
            ));
        }
    }

    /**
     * @throws Throwable
     */
    public function save(): void
    {
        $this->update();
    }

    /**
     * The vocabulary is scoped to the store of the group, not of the key, so the group decides which
     * entries a relation may name.
     *
     * Which store that is, is read under a lock on the group row, and so as of now rather than from the
     * snapshot of an enclosing transaction: GroupConfig\Dao::update() takes the same lock before it
     * looks for a tag that a move would strand, so this write and that move cannot both decide that
     * there is nothing to refuse. Whichever takes the row first, the other sees its outcome - a tag on
     * its way in is either found by the move and refuses it, or waits for the move and then resolves
     * against the store the group ended up in.
     *
     * The resolved row is locked rather than only read: the lock is held until the enclosing write
     * commits, so a sub group deletion either clears the tag this write leaves behind or waits for the
     * write and then finds it. SubGroupConfig\Dao::delete() takes the same lock before it clears tags.
     *
     * The two locks are taken in that order - the group, then the entry, then the relation the write
     * itself touches - which is the order SubGroupConfig\Dao::lockGroupsBeforeTheEntry() and
     * GroupConfig\Dao::update() take the same tables in, so none of the three can wait on another.
     *
     * What is stored is the identifier of the resolved row, not the spelling the caller passed: the
     * column collation is case insensitive, so two spellings resolve to one entry here and would
     * otherwise read as two sub groups in KeyGroupRelationCache::getSubGroupOrder().
     *
     * @throws InvalidConfigException
     */
    private function resolveSubGroup(): void
    {
        $subGroup = $this->model->getSubGroup();
        if ($subGroup === null) {
            return;
        }

        $storeId = $this->db->fetchOne(
            'SELECT `storeId` FROM `' . Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '`'
            . ' WHERE `id` = ? FOR UPDATE',
            [$this->model->getGroupId()]
        );

        // locking a single row by the unique (storeId, identifier) index, rather than the join of the two
        $resolved = $storeId === false ? false : $this->db->fetchOne(
            'SELECT `identifier` FROM `' . Classificationstore\SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS . '`'
            . ' WHERE `storeId` = ? AND `identifier` = ? FOR UPDATE',
            [$storeId, $subGroup]
        );

        if (!is_string($resolved)) {
            throw new InvalidConfigException(sprintf(
                'The sub group "%s" does not exist in the store of group %s.',
                $subGroup,
                $this->model->getGroupId()
            ));
        }

        $this->model->setSubGroup($resolved);
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

    /**
     * The sub group is resolved and the row written as one unit, so that the entry the tag names
     * cannot be deleted in between.
     *
     * @throws Throwable
     */
    public function update(): void
    {
        $this->writeAtomically(function (): void {
            $this->resolveSubGroup();

            // read after resolveSubGroup(), so that the resolved identifier is the one written
            $type = $this->model->getObjectVars();
            $validTableColumns = $this->getValidTableColumns(self::TABLE_NAME_RELATIONS);
            $data = [];

            foreach ($type as $key => $value) {
                if (!in_array($key, $validTableColumns)) {
                    continue;
                }

                // a relation built with `new` carries no per assignment data, and must not clear a stored one
                if ($this->isUninitializedPerAssignmentColumn($key)) {
                    continue;
                }

                // the two JSON columns hold JSON text, not a PHP serialization payload
                if ($key === self::COLUMN_METADATA || $key === self::COLUMN_DEFINITION_OVERRIDE) {
                    $data[$key] = $value === null ? null : json_encode($value);

                    continue;
                }

                if (is_bool($value)) {
                    $value = (int) $value;
                }
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                }

                $data[$key] = $value;
            }

            Helper::upsert(
                $this->db,
                self::TABLE_NAME_RELATIONS,
                $data,
                $this->getPrimaryKey(self::TABLE_NAME_RELATIONS)
            );
        });

        // only once the write is committed, so that a refused one does not drop a correct answer
        $this->clearRelationCache();
    }

    /**
     * Mirrors the engineInitialized guard of StoreConfig: a column is written only once it was
     * loaded or explicitly set.
     */
    private function isUninitializedPerAssignmentColumn(string $column): bool
    {
        return match ($column) {
            self::COLUMN_METADATA => !$this->model->isMetadataInitialized(),
            self::COLUMN_DEFINITION_OVERRIDE => !$this->model->isDefinitionOverrideInitialized(),
            self::COLUMN_SUB_GROUP => !$this->model->isSubGroupInitialized(),
            default => false,
        };
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
