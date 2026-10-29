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

namespace Pimcore\Model\DataObject\Classificationstore\StoreConfig;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierDaoTrait;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\StoreConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use IdentifierDaoTrait;

    const TABLE_NAME_STORES = 'classificationstore_stores';

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

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_STORES . ' WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('StoreConfig with id: ' . $this->model->getId() . ' does not exist');
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

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_STORES . ' WHERE name = ?', [$name]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store config with name "%s" does not exist.', $name));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByIdentifier(string $identifier): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_STORES . ' WHERE identifier = ?', [$identifier]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store config with identifier "%s" does not exist.', $identifier));
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
            'SELECT id FROM ' . self::TABLE_NAME_STORES . ' WHERE identifier = ? AND id != ?',
            [$identifier, $this->model->getId() ?? 0]
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
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME_STORES, ['id' => $this->model->getId()]);
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
            $identifierPending = $this->isIdentifierPending(self::TABLE_NAME_STORES, $id, $identifier);

            $data = [];
            $type = $this->model->getObjectVars();

            foreach ($type as $key => $value) {
                // a store config built with `new` carries no engine, and must not reset a flipped store
                if ($key === 'engine' && !$this->model->isEngineInitialized()) {
                    continue;
                }

                // written by writeIdentifier(), which is the only place allowed to touch the column
                if ($key === 'identifier') {
                    continue;
                }

                if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_STORES))) {
                    if (is_bool($value)) {
                        $value = (int) $value;
                    }
                    if (is_array($value) || is_object($value)) {
                        $value = \Pimcore\Tool\Serialize::serialize($value);
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_STORES, $data, ['id' => $this->model->getId()]);

            if ($identifierPending) {
                $this->writeIdentifier(self::TABLE_NAME_STORES, (int) $id, (string) $identifier);
            }
        });
    }

    public function create(): void
    {
        $this->db->insert(self::TABLE_NAME_STORES, []);
        $this->model->setId((int) $this->db->lastInsertId());
    }
}
