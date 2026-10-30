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

namespace Pimcore\Model\DataObject\Classificationstore\GroupConfig;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierDaoTrait;
use Pimcore\Model\DataObject\Classificationstore\Traits\LabelCleanupDaoTrait;
use Throwable;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\GroupConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use IdentifierDaoTrait;
    use LabelCleanupDaoTrait;

    const TABLE_NAME_GROUPS = 'classificationstore_groups';

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_GROUPS . ' WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('GroupConfig with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     *
     * @throws Exception
     */
    public function getByName(?string $name = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();
        $storeId = $this->model->getStoreId();

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_GROUPS . ' WHERE name = ? and storeId = ?', [$name, $storeId]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store group config with name "%s" does not exist.', $name));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByIdentifier(string $identifier): void
    {
        $data = $this->db->fetchAssociative(
            'SELECT * FROM ' . self::TABLE_NAME_GROUPS . ' WHERE identifier = ? AND storeId = ?',
            [$identifier, $this->model->getStoreId()]
        );

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store group config with identifier "%s" does not exist.', $identifier));
        }
    }

    /**
     * @throws Model\Exception\InvalidConfigException
     */
    public function assertIdentifierIsUnique(): void
    {
        $identifier = $this->model->getIdentifier();
        if ($identifier === null) {
            return;
        }

        $used = $this->db->fetchOne(
            'SELECT id FROM ' . self::TABLE_NAME_GROUPS . ' WHERE identifier = ? AND storeId = ? AND id != ?',
            [$identifier, $this->model->getStoreId(), $this->model->getId() ?? 0]
        );

        if ($used !== false) {
            throw new Model\Exception\InvalidConfigException(sprintf('The identifier "%s" is already in use.', $identifier));
        }
    }

    public function hasChildren(): bool
    {
        if (!$this->model->getId()) {
            return false;
        }

        return (bool) $this->db->fetchOne('SELECT COUNT(*) as amount FROM ' . self::TABLE_NAME_GROUPS . ' WHERE parentId = ?', [$this->model->getId()]);
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->createAndUpdate();

            return;
        }

        $this->update();
    }

    /**
     * Deletes object from database, together with this group's own labels
     *
     * @throws Throwable
     */
    public function delete(): void
    {
        $this->deleteWithLabels(self::TABLE_NAME_GROUPS, LabelEntityType::Group);
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        // the row itself and the identifier are written as one unit
        $this->writeAtomically(function (): void {
            $id = $this->model->getId();
            $identifier = $this->model->getIdentifier();
            $this->assertNoTagIsStrandedByAMove($id);
            $identifierPending = $this->isIdentifierPending(self::TABLE_NAME_GROUPS, $id, $identifier);

            $ts = time();
            $this->model->setModificationDate($ts);

            $data = [];
            $type = $this->model->getObjectVars();

            foreach ($type as $key => $value) {
                // written by writeIdentifier(), which is the only place allowed to touch the column
                if ($key === 'identifier') {
                    continue;
                }

                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_GROUPS))) {
                    if (is_bool($value)) {
                        $value = (int) $value;
                    }
                    if (is_array($value) || is_object($value)) {
                        $value = \Pimcore\Tool\Serialize::serialize($value);
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_GROUPS, $data, ['id' => $this->model->getId()]);

            if ($identifierPending) {
                $this->writeIdentifier(self::TABLE_NAME_GROUPS, (int) $id, (string) $identifier);
            }
        });
    }

    public function create(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert(self::TABLE_NAME_GROUPS, []);

        $this->model->setId((int) $this->db->lastInsertId());
    }

    /**
     * The sub group a key assignment names is an entry in the vocabulary of the store the group
     * belongs to, so a group that moves takes its assignments to a store whose vocabulary knows
     * nothing of their tags: the tag resolves to nothing, deleting the entry it came from no longer
     * reaches it, and saving that assignment again is refused. A move is therefore refused for as
     * long as one of the group's assignments carries a tag - an untagged group moves as before.
     *
     * The stored store is read as of now and held until the move commits:
     * KeyGroupRelation\Dao::resolveSubGroup() takes the same lock on the group row while it resolves
     * a tag, so a tag on its way in is either seen here or waits for the move and then resolves
     * against the store the group ends up in. The tags are read the same way, because the row that
     * carries one may have been committed after the snapshot of an enclosing transaction.
     *
     * The group before the relations it carries, which is the order the other two writers of this family
     * take these tables in as well - see KeyGroupRelation\Dao::resolveSubGroup() and
     * SubGroupConfig\Dao::lockGroupsBeforeTheEntry().
     *
     * A row that carries no store yet is the placeholder create() inserts, which update() fills in.
     *
     * @throws Model\Exception\InvalidConfigException
     */
    private function assertNoTagIsStrandedByAMove(?int $id): void
    {
        if ($id === null) {
            return;
        }

        $stored = $this->db->fetchOne(
            'SELECT `storeId` FROM `' . self::TABLE_NAME_GROUPS . '` WHERE `id` = ? FOR UPDATE',
            [$id]
        );

        if ($stored === false || $stored === null || (int) $stored === $this->model->getStoreId()) {
            return;
        }

        $tag = $this->db->fetchOne(
            'SELECT `subGroup` FROM `' . KeyGroupRelationDao::TABLE_NAME_RELATIONS . '`'
            . ' WHERE `groupId` = ? AND `subGroup` IS NOT NULL LIMIT 1 FOR UPDATE',
            [$id]
        );

        if ($tag === false) {
            return;
        }

        throw new Model\Exception\InvalidConfigException(sprintf(
            'The store of group %d cannot be changed from %d to %d while a key assignment of it'
            . ' carries the sub group "%s".',
            $id,
            (int) $stored,
            $this->model->getStoreId(),
            (string) $tag
        ));
    }
}
