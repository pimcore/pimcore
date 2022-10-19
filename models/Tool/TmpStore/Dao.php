<?php
declare(strict_types=1);

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

namespace Pimcore\Model\Tool\TmpStore;

use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\TmpStore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param string $id
     * @param mixed $data
     * @param string $tag
     * @param int $lifetime
     *
     * @return bool
     */
    public function add(string $id, mixed $data, string $tag, int $lifetime): bool
    {
        try {
            $serialized = false;
            if (is_object($data) || is_array($data)) {
                $serialized = true;
                $data = serialize($data);
            }

            Helper::insertOrUpdate($this->db, 'tmp_store', [
                'id' => $id,
                'data' => $data,
                'tag' => $tag,
                'date' => time(),
                'expiryDate' => (time() + $lifetime),
                'serialized' => (int) $serialized,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(string $id)
    {
        $this->db->delete('tmp_store', ['id' => $id]);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function getById(string $id): bool
    {
        $item = $this->db->fetchAssociative('SELECT * FROM tmp_store WHERE id = ?', [$id]);

        if (is_array($item) && array_key_exists('id', $item)) {
            if ($item['serialized']) {
                $item['data'] = unserialize($item['data']);
            }

            $this->assignVariablesToModel($item);

            return true;
        }

        return false;
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    public function getIdsByTag(string $tag): array
    {
        $items = $this->db->fetchFirstColumn('SELECT id FROM tmp_store WHERE tag = ?', [$tag]);

        return $items;
    }
}
