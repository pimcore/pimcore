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

namespace Pimcore\Model\Tool\SettingsStore;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\Tool\SettingsStore;

/**
 * @internal
 *
 * @property SettingsStore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME = 'settings_store';

    public function set(string $id, float|bool|int|string $data, string $type = SettingsStore::TYPE_STRING, ?string $scope = null): bool
    {
        try {
            Helper::upsert($this->db, self::TABLE_NAME, [
                'id' => $id,
                'data' => $data,
                'scope' => (string) $scope,
                'type' => $type,
            ], $this->getPrimaryKey(self::TABLE_NAME));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(string $id, ?string $scope = null): int|string
    {
        return $this->db->delete(self::TABLE_NAME, [
            'id' => $id,
            'scope' => (string) $scope,
        ]);
    }

    /**
     * @throws Model\Exception\NotFoundException
     */
    public function getById(string $id, ?string $scope = null): void
    {
        $item = $this->db->fetchAssociative('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = :id AND scope = :scope', [
            'id' => $id,
            'scope' => (string) $scope,
        ]);

        if (!$item) {
            throw new Model\Exception\NotFoundException('settings store with id ' . $id . ' and scope ' . $scope . ' not found');
        }

        $this->assignVariablesToModel($item);

        $data = $item['data'] ?? null;
        $this->model->setData($data);
    }

    public function getIdsByScope(string $scope): array
    {
        return $this->db->fetchFirstColumn('SELECT id FROM ' . self::TABLE_NAME . ' WHERE scope = ?', [$scope]);
    }
}
