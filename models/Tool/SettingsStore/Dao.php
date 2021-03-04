<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\SettingsStore;

use Pimcore\Model;
use Pimcore\Model\Tool\SettingsStore;

/**
 * @property SettingsStore $model
 */
class Dao extends Model\Dao\AbstractDao
{

    const TABLE_NAME = 'settings_store';

    /**
     * @param string $id
     * @param mixed $data
     * @param string|null $scope
     * @param string $type
     * @return bool
     */
    public function set(string $id, $data, string $scope = null, string $type = 'string'): bool
    {
        try {
            $this->db->insertOrUpdate(self::TABLE_NAME, [
                'id' => $id,
                'data' => $data,
                'scope' => $scope,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function delete(string $id)
    {
        return $this->db->delete(self::TABLE_NAME, ['id' => $id]);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function getById(string $id): bool
    {
        $item = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = ?', $id);

        if (is_array($item) && array_key_exists('id', $item)) {
            $this->assignVariablesToModel($item);

            $data = $item['data'] ?? null;
            settype($data, $this->model->getType());
            $this->model->setData($data);
            return true;
        }

        return false;
    }

    /**
     * @param string $scope
     * @return array
     */
    public function getIdsByScope(string $scope): array
    {
        return $this->db->fetchCol('SELECT id FROM ' . self::TABLE_NAME . ' WHERE scope = ?', [$scope]);
    }
}
