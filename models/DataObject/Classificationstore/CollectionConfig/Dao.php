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

namespace Pimcore\Model\DataObject\Classificationstore\CollectionConfig;

use Exception;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Classificationstore\CollectionConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME_COLLECTIONS = 'classificationstore_collections';

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

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_COLLECTIONS . ' WHERE id = ?', [$this->model->getId()]);

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('CollectionConfig with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName(string $name = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();
        $storeId = $this->model->getStoreId();

        $data = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME_COLLECTIONS . ' WHERE name = ? and storeId = ?', [$name, $storeId]);

        if (!empty($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Classification store collection config with name "%s" does not exist.', $name));
        }
    }

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
        $this->db->delete(self::TABLE_NAME_COLLECTIONS, ['id' => $this->model->getId()]);
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
            if (in_array($key, $this->getValidTableColumns(self::TABLE_NAME_COLLECTIONS))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                if (is_array($value) || is_object($value)) {
                    $value = \Pimcore\Tool\Serialize::serialize($value);
                }

                $data[$key] = $value;
            }
        }

        $this->db->update(self::TABLE_NAME_COLLECTIONS, $data, ['id' => $this->model->getId()]);
    }

    public function create(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert(self::TABLE_NAME_COLLECTIONS, []);

        $this->model->setId((int) $this->db->lastInsertId());
    }
}
