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
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Element\Service as ElementService;

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

    /**
     * Workspace rows of the element and all its children.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getWorkspaces(): array
    {
        $path = $this->model->getPath();

        return $this->db->fetchAllAssociative(
            'SELECT * FROM users_workspaces_' . $this->model->getType() . ' WHERE cpath = ? OR cpath LIKE ?',
            [$path, Helper::escapeLike($path) . '/%']
        );
    }

    /**
     * Re-inserts all rows or none.
     *
     * @param array<mixed> $rows rows from getWorkspaces()
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function restoreWorkspaces(array $rows): void
    {
        $type = $this->model->getType();
        $table = 'users_workspaces_' . $type;
        $columns = array_flip($this->getValidTableColumns($table));

        $this->db->transactional(function () use ($rows, $type, $table, $columns): void {
            $paths = [];

            foreach ($rows as $row) {
                if (!is_array($row) || !isset($row['cid'], $row['userId'])) {
                    continue;
                }

                $cid = (int) $row['cid'];
                if (!array_key_exists($cid, $paths)) {
                    $paths[$cid] = ElementService::getElementById($type, $cid, ['force' => true])?->getRealFullPath();
                }

                $userId = $this->db->fetchOne('SELECT id FROM users WHERE id = ?', [(int) $row['userId']]);
                if ($paths[$cid] === null || $userId === false) {
                    continue;
                }

                $row = array_intersect_key($row, $columns);
                $row['cpath'] = $paths[$cid];

                $this->db->insert($table, Helper::quoteDataIdentifiers($this->db, $row));
            }
        });
    }
}
