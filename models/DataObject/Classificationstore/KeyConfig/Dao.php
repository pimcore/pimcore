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

namespace Pimcore\Model\DataObject\Classificationstore\KeyConfig;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierDaoTrait;
use Pimcore\Model\DataObject\Classificationstore\Traits\LabelCleanupDaoTrait;
use Throwable;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\KeyConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use IdentifierDaoTrait;
    use LabelCleanupDaoTrait;

    const TABLE_NAME_KEYS = 'classificationstore_keys';

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

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_KEYS . ' WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $data['enabled'] = (bool)$data['enabled'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('KeyConfig with id: ' . $this->model->getId() . ' does not exist');
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

        $stmt = 'SELECT * FROM ' . self::TABLE_NAME_KEYS . ' WHERE name = ' . $this->db->quote($name) . ' and storeId = ' . $storeId;

        $data = $this->db->fetchAssociative($stmt);

        if ($data) {
            $data['enabled'] = (bool)$data['enabled'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store key config with name "%s" does not exist.', $name));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByIdentifier(string $identifier): void
    {
        $data = $this->db->fetchAssociative(
            'SELECT * FROM ' . self::TABLE_NAME_KEYS . ' WHERE identifier = ? AND storeId = ?',
            [$identifier, $this->model->getStoreId()]
        );

        if ($data) {
            $data['enabled'] = (bool)$data['enabled'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store key config with identifier "%s" does not exist.', $identifier));
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
            'SELECT id FROM ' . self::TABLE_NAME_KEYS . ' WHERE identifier = ? AND storeId = ? AND id != ?',
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
     * Deletes object from database, together with this key's own labels
     *
     * @throws Throwable
     */
    public function delete(): void
    {
        $this->deleteWithLabels(self::TABLE_NAME_KEYS, LabelEntityType::Key);
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        // the group relations deleted below, the row itself and the identifier change as one unit
        $this->writeAtomically(function (): void {
            $id = $this->model->getId();
            $identifier = $this->model->getIdentifier();
            $identifierPending = $this->isIdentifierPending(self::TABLE_NAME_KEYS, $id, $identifier);

            $ts = time();
            $this->model->setModificationDate($ts);

            $data = [];
            $type = $this->model->getObjectVars();

            foreach ($type as $key => $value) {
                // written by writeIdentifier(), which is the only place allowed to touch the column
                if ($key === 'identifier') {
                    continue;
                }

                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_KEYS))) {
                    if (is_bool($value)) {
                        $value = (int) $value;

                        if (!$value && $key === 'enabled') {
                            $this->db->delete(
                                Model\DataObject\Classificationstore\KeyGroupRelation\Dao::TABLE_NAME_RELATIONS,
                                ['keyId' => $this->model->getId()]);
                        }
                    }
                    if (is_array($value) || is_object($value)) {
                        if ($this->model->getType() == 'select') {
                            $value = json_encode($value);
                        } else {
                            $value = \Pimcore\Tool\Serialize::serialize($value);
                        }
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_KEYS, $data, ['id' => $this->model->getId()]);

            if ($identifierPending) {
                $this->writeIdentifier(self::TABLE_NAME_KEYS, (int) $id, (string) $identifier);
            }
        });
    }

    public function create(): void
    {
        $ts = time();
        $this->model->setCreationDate($ts);
        $this->model->setModificationDate($ts);

        $this->db->insert(self::TABLE_NAME_KEYS, []);

        $this->model->setId((int) $this->db->lastInsertId());
    }
}
