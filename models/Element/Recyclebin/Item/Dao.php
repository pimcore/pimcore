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

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Pimcore\Db\Helper;
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
        // backslashes are valid in object keys but are the LIKE escape character
        $childPattern = Helper::escapeLike(str_replace('\\', '\\\\', $path)) . '/%';

        return $this->db->fetchAllAssociative(
            'SELECT * FROM users_workspaces_' . $this->model->getType() . ' WHERE cpath = ? OR cpath LIKE ?',
            [$path, $childPattern]
        );
    }

    /**
     * Rows of users and roles that still exist.
     *
     * @param array<mixed> $rows rows from getWorkspaces()
     *
     * @return array<int, list<array<string, mixed>>> keyed by element id
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRestorableWorkspaces(array $rows): array
    {
        $rows = array_filter(
            $rows,
            static fn (mixed $row): bool => is_array($row) && isset($row['cid'], $row['userId'])
        );
        if ($rows === []) {
            return [];
        }

        $userIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['userId'], $rows)));
        $existingUserIds = array_flip(array_map('intval', $this->db->fetchFirstColumn(
            'SELECT id FROM users WHERE id IN (?)',
            [$userIds],
            [ArrayParameterType::INTEGER]
        )));
        $columns = array_flip($this->getValidTableColumns('users_workspaces_' . $this->model->getType()));

        $workspaces = [];
        foreach ($rows as $row) {
            if (isset($existingUserIds[(int) $row['userId']])) {
                $workspaces[(int) $row['cid']][] = array_intersect_key($row, $columns);
            }
        }

        return $workspaces;
    }

    /**
     * Re-inserts the rows of one restored element, all or none.
     *
     * @param list<array<string, mixed>> $rows rows from getRestorableWorkspaces()
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertWorkspaces(array $rows, string $cpath): void
    {
        $table = 'users_workspaces_' . $this->model->getType();

        $this->db->transactional(function () use ($rows, $cpath, $table): void {
            foreach ($rows as $row) {
                $row['cpath'] = $cpath;
                $this->db->insert($table, Helper::quoteDataIdentifiers($this->db, $row));
            }
        });
    }
}
