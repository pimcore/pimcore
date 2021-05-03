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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\SettingsStore;

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

    /**
     * @param string $id
     * @param int|string|bool|float $data
     * @param string $type
     * @param string|null $scope
     *
     * @return bool
     */
    public function set(string $id, $data, string $type = 'string', ?string $scope = null): bool
    {
        try {
            $this->db->insertOrUpdate(self::TABLE_NAME, [
                'id' => $id,
                'data' => $data,
                'scope' => (string) $scope,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $id
     * @param string|null $scope
     *
     * @return mixed
     */
    public function delete(string $id, ?string $scope = null)
    {
        return $this->db->delete(self::TABLE_NAME, [
            'id' => $id,
            'scope' => (string) $scope,
        ]);
    }

    /**
     * @param string $id
     * @param string|null $scope
     *
     * @return bool
     */
    public function getById(string $id, ?string $scope = null): bool
    {
        $item = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = :id AND scope = :scope', [
            'id' => $id,
            'scope' => (string) $scope,
        ]);

        if (is_array($item) && array_key_exists('id', $item)) {
            $this->assignVariablesToModel($item);

            $data = $item['data'] ?? null;
            $this->model->setData($data);

            return true;
        }

        return false;
    }

    /**
     * @param string $scope
     *
     * @return array
     */
    public function getIdsByScope(string $scope): array
    {
        return $this->db->fetchCol('SELECT id FROM ' . self::TABLE_NAME . ' WHERE scope = ?', [$scope]);
    }
}
