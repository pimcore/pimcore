<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

use Exception;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao as GroupConfigDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao as KeyGroupRelationDao;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierDaoTrait;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\SubGroupConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use IdentifierDaoTrait;

    const TABLE_NAME_SUBGROUPS = 'classificationstore_subgroups';

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

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_SUBGROUPS . ' WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('SubGroupConfig with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName(?string $name = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();
        $storeId = $this->model->getStoreId();

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_SUBGROUPS . ' WHERE name = ? and storeId = ?', [$name, $storeId]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store sub group config with name "%s" does not exist.', $name));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByIdentifier(string $identifier): void
    {
        $data = $this->db->fetchAssociative(
            'SELECT * FROM ' . self::TABLE_NAME_SUBGROUPS . ' WHERE identifier = ? AND storeId = ?',
            [$identifier, $this->model->getStoreId()]
        );

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store sub group config with identifier "%s" does not exist.', $identifier));
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
            'SELECT id FROM ' . self::TABLE_NAME_SUBGROUPS . ' WHERE identifier = ? AND storeId = ? AND id != ?',
            [$identifier, $this->model->getStoreId(), $this->model->getId() ?? 0]
        );

        if ($used !== false) {
            throw new Model\Exception\InvalidConfigException(sprintf('The identifier "%s" is already in use.', $identifier));
        }
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
     * Deletes object from database, together with the tag it leaves behind on the key assignments of
     * its store: the vocabulary row and the tags are one unit of work, so a failure of either leaves
     * both as they were.
     *
     * Which tags those are is read from the stored row rather than taken from the instance, so that an
     * instance carrying only an id still clears them, and one that was never saved clears none.
     *
     * @throws Exception
     */
    public function delete(): void
    {
        $id = $this->model->getId();
        if ($id === null) {
            return;
        }

        $taggedGroupIds = [];

        $this->writeAtomically(function () use ($id, &$taggedGroupIds): void {
            // the lock KeyGroupRelation\Dao::resolveSubGroup() contends with, taken before any tag is read
            $stored = $this->db->fetchAssociative(
                'SELECT `storeId`, `identifier` FROM `' . self::TABLE_NAME_SUBGROUPS . '` WHERE `id` = ? FOR UPDATE',
                [$id]
            );

            if ($stored === false) {
                return;
            }

            $identifier = $stored['identifier'];
            $taggedGroupIds = $this->clearTags(
                (int) $stored['storeId'],
                is_string($identifier) ? $identifier : null
            );

            $this->db->delete(self::TABLE_NAME_SUBGROUPS, ['id' => $id]);
        });

        // only once the delete is committed, so that a refused one does not drop a correct answer
        $relationCache = Pimcore::getContainer()->get(KeyGroupRelationCache::class);
        foreach ($taggedGroupIds as $groupId) {
            $relationCache->clearGroup($groupId);
        }
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
            $this->assertStoreIsUnchanged($id);
            $identifierPending = $this->isIdentifierPending(self::TABLE_NAME_SUBGROUPS, $id, $identifier);

            $ts = time();
            $this->model->setModificationDate($ts);

            $data = [];
            $type = $this->model->getObjectVars();

            foreach ($type as $key => $value) {
                // written by writeIdentifier(), which is the only place allowed to touch the column
                if ($key === 'identifier') {
                    continue;
                }

                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_SUBGROUPS))) {
                    if (is_bool($value)) {
                        $value = (int) $value;
                    }
                    if (is_array($value) || is_object($value)) {
                        $value = Serialize::serialize($value);
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_SUBGROUPS, $data, ['id' => $this->model->getId()]);

            if ($identifierPending) {
                $this->writeIdentifier(self::TABLE_NAME_SUBGROUPS, (int) $id, (string) $identifier);
            }
        });
    }

    public function create(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert(self::TABLE_NAME_SUBGROUPS, []);

        $this->model->setId((int) $this->db->lastInsertId());
    }

    /**
     * The store of a stored entry is what scopes its tags: the key assignments of that store name it by
     * identifier, and nothing in them would follow the entry to a second store. Moving it would leave
     * them resolving to nothing - refusing the save fails and reaches no further.
     *
     * A row that carries no store yet is the placeholder create() inserts, which update() fills in.
     *
     * @throws Model\Exception\InvalidConfigException
     */
    private function assertStoreIsUnchanged(?int $id): void
    {
        if ($id === null) {
            return;
        }

        $stored = $this->db->fetchOne(
            'SELECT `storeId` FROM `' . self::TABLE_NAME_SUBGROUPS . '` WHERE `id` = ?',
            [$id]
        );

        if ($stored === false || $stored === null || (int) $stored === $this->model->getStoreId()) {
            return;
        }

        throw new Model\Exception\InvalidConfigException(sprintf(
            'The store of sub group %d cannot be changed from %d to %d.',
            $id,
            (int) $stored,
            $this->model->getStoreId()
        ));
    }

    /**
     * Clears the tag from every key assignment of the store that carries it, and reports the groups
     * those assignments belong to.
     *
     * @return int[]
     */
    private function clearTags(int $storeId, ?string $identifier): array
    {
        if ($identifier === null) {
            return [];
        }

        $parameters = [$storeId, $identifier];

        $taggedGroupIds = $this->db->fetchFirstColumn(
            'SELECT DISTINCT `r`.`groupId`'
            . ' FROM `' . KeyGroupRelationDao::TABLE_NAME_RELATIONS . '` `r`'
            . ' INNER JOIN `' . GroupConfigDao::TABLE_NAME_GROUPS . '` `g` ON `g`.`id` = `r`.`groupId`'
            . ' WHERE `g`.`storeId` = ? AND `r`.`subGroup` = ?',
            $parameters
        );
        if (!$taggedGroupIds) {
            return [];
        }

        $this->db->executeStatement(
            'UPDATE `' . KeyGroupRelationDao::TABLE_NAME_RELATIONS . '` `r`'
            . ' INNER JOIN `' . GroupConfigDao::TABLE_NAME_GROUPS . '` `g` ON `g`.`id` = `r`.`groupId`'
            . ' SET `r`.`subGroup` = NULL'
            . ' WHERE `g`.`storeId` = ? AND `r`.`subGroup` = ?',
            $parameters
        );

        return array_map('intval', $taggedGroupIds);
    }
}
