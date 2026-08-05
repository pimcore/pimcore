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

namespace Pimcore\Model\Element\Recyclebin\Item;

use Exception;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Element\Recyclebin\Item $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Exception
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM recyclebin WHERE id = ?', [$id]);

        if (!$data) {
            throw new Model\Exception\NotFoundException('Recyclebin item with id ' . $id . ' not found');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     *
     * @todo: not all save methods return a boolean, why this one?
     */
    public function save(): bool
    {
        $version = $this->model->getObjectVars();
        $data = [];

        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('recyclebin'))) {
                $data[$key] = $value;
            }
        }

        try {
            $this->db->insert('recyclebin', $data);
            $this->model->setId((int) $this->db->lastInsertId());
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        return true;
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('recyclebin', ['id' => $this->model->getId()]);
    }
}
