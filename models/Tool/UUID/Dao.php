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

namespace Pimcore\Model\Tool\UUID;

use Doctrine\DBAL\Types\Types;
use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\UUID $model
 */
class Dao extends Model\Dao\AbstractDao
{
    const TABLE_NAME = 'uuids';

    public function save()
    {
        $data = $this->getValidObjectVars();

        Helper::insertOrUpdate($this->db, self::TABLE_NAME, $data);
    }

    public function create()
    {
        $data = $this->getValidObjectVars();

        $this->db->insert(self::TABLE_NAME, $data);
    }

    /**
     * @return array
     */
    private function getValidObjectVars()
    {
        $data = $this->model->getObjectVars();

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->getValidTableColumns(static::TABLE_NAME))) {
                unset($data[$key]);
            }
        }

        return $data;
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

        $itemId = $this->model->getItemId();
        $type = $this->model->getType();

        $this->db->delete(self::TABLE_NAME, ['itemId' => $itemId, 'type' => $type, 'uuid' => $uuid]);
    }

    /**
     * @param string $uuid
     *
     * @return Model\Tool\UUID
     */
    public function getByUuid($uuid)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid, Types::STRING);

        $data = $queryBuilder
            ->execute()
            ->fetchAssociative();

        $model = new Model\Tool\UUID();
        $model->setValues($data);

        return $model;
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    public function exists($uuid)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('uuid')
            ->from(self::TABLE_NAME)
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid, Types::STRING);


        $result = $queryBuilder
            ->execute()
            ->fetchOne();

        return (bool) $result;
    }
}
