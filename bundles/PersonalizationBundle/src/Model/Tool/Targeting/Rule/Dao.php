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

namespace Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\Rule;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\Rule;
use Pimcore\Model;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property Rule|Dao $model
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
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM targeting_rules WHERE id = ?', [$this->model->getId()]);

        if (!empty($data['id'])) {
            $data['conditions'] = (isset($data['conditions']) ? Serialize::unserialize($data['conditions']) : []);
            $data['actions'] = (isset($data['actions']) ? Serialize::unserialize($data['actions']) : []);

            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('target with id ' . $this->model->getId() . " doesn't exist");
        }
    }

    /**
     * @param string|null $name
     *
     * @throws \Exception
     */
    public function getByName(string $name = null): void
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $data = $this->db->fetchAllAssociative('SELECT id FROM targeting_rules WHERE name = ?', [$this->model->getName()]);

        if (count($data) === 1) {
            $this->getById($data[0]['id']);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Targeting rule with name "%s" does not exist.',
                $this->model->getName()
            ));
        }
    }

    /**
     * Save object to database
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('targeting_rules', ['id' => $this->model->getId()]);
    }

    /**
     * @throws \Exception
     */
    public function update(): void
    {
        $type = $this->model->getObjectVars();
        $data = [];

        foreach ($type as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('targeting_rules'))) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                }
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('targeting_rules', $data, ['id' => $this->model->getId()]);
    }

    public function create(): void
    {
        $this->db->insert('targeting_rules', []);
        $this->model->setId((int) $this->db->lastInsertId());
    }
}
