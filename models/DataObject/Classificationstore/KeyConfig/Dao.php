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

namespace Pimcore\Model\DataObject\Classificationstore\KeyConfig;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\KeyConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME_KEYS = 'classificationstore_keys';

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id = null): void
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
    public function getByName(string $name = null): void
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
     * @throws Exception
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete(self::TABLE_NAME_KEYS, ['id' => $this->model->getId()]);
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);

        $data = [];
        $type = $this->model->getObjectVars();

        foreach ($type as $key => $value) {
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
