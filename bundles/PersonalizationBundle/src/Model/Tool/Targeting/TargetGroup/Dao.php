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

namespace Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Model;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property TargetGroup $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int|null $id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id = null): void
    {
        if (null !== $id) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM targeting_target_groups WHERE id = ?', [$this->model->getId()]);

        if (!empty($data['id'])) {
            $data['actions'] = (isset($data['actions']) ? Serialize::unserialize($data['actions']) : []);

            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('Target Group with id ' . $this->model->getId() . " doesn't exist");
        }
    }

    /**
     * @param string|null $name
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName(string $name = null): void
    {
        if (null !== $name) {
            $this->model->setName($name);
        }

        $data = $this->db->fetchAllAssociative('SELECT id FROM targeting_target_groups WHERE name = ?', [$this->model->getName()]);

        if (count($data) === 1) {
            $this->getById($data[0]['id']);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Targeting group with name "%s" does not exist or is not unique.',
                $this->model->getName()
            ));
        }
    }

    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    public function delete(): void
    {
        $this->db->delete('targeting_target_groups', ['id' => $this->model->getId()]);
    }

    public function update(): void
    {
        $type = $this->model->getObjectVars();
        $data = [];

        foreach ($type as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('targeting_target_groups'))) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                }

                if (is_bool($value)) {
                    $value = (int)$value;
                }

                $data[$key] = $value;
            }
        }

        $this->db->update('targeting_target_groups', $data, ['id' => $this->model->getId()]);
    }

    public function create(): void
    {
        $this->db->insert('targeting_target_groups', []);
        $this->model->setId((int) $this->db->lastInsertId());
    }
}
