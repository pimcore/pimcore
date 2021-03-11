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

namespace Pimcore\Model\Tool\Targeting\TargetGroup;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Pimcore\Tool\Serialize;

/**
 * @property TargetGroup $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int|null $id
     *
     * @throws \Exception
     */
    public function getById(int $id = null)
    {
        if (null !== $id) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM targeting_target_groups WHERE id = ?', $this->model->getId());

        if (!empty($data['id'])) {
            $data['actions'] = (isset($data['actions']) ? Serialize::unserialize($data['actions']) : []);

            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('Target Group with id ' . $this->model->getId() . " doesn't exist");
        }
    }

    public function getByName(string $name = null)
    {
        if (null !== $name) {
            $this->model->setName($name);
        }

        $data = $this->db->fetchAll('SELECT id FROM targeting_target_groups WHERE name = ?', [$this->model->getName()]);

        if (count($data) === 1) {
            $this->getById($data[0]['id']);
        } else {
            throw new \Exception(sprintf('Target Group with name %s doesn\'t exist or isn\'t unique', $this->model->getName()));
        }
    }

    public function save()
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    public function delete()
    {
        $this->db->delete('targeting_target_groups', ['id' => $this->model->getId()]);
    }

    public function update()
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

    public function create()
    {
        $this->db->insert('targeting_target_groups', []);
        $this->model->setId($this->db->lastInsertId());
    }
}
