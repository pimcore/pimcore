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

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Tool\UUID $model
 */
class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME = 'uuids';

    public function save()
    {
        $data = $this->model->getObjectVars();

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->getValidTableColumns(static::TABLE_NAME))) {
                unset($data[$key]);
            }
        }

        $this->db->insertOrUpdate(self::TABLE_NAME, $data);
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        $uuid = $this->model->getUuid();
        if (!$uuid) {
            throw new \Exception("Couldn't delete UUID - no UUID specified.");
        }
        $this->db->delete(self::TABLE_NAME, ['uuid' => $uuid]);
    }

    /**
     * @param string $uuid
     *
     * @return Model\Tool\UUID
     */
    public function getByUuid($uuid)
    {
        $data = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME ." where uuid='" . $uuid . "'");
        $model = new Model\Tool\UUID();
        $model->setValues($data);

        return $model;
    }
}
