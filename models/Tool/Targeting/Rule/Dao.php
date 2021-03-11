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

namespace Pimcore\Model\Tool\Targeting\Rule;

use Pimcore\Model;
use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Tool\Serialize;

/**
 * @property Rule|Model\Tool\Targeting\Rule\Dao $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param int|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM targeting_rules WHERE id = ?', $this->model->getId());

        if (!empty($data['id'])) {
            $data['conditions'] = (isset($data['conditions']) ? Serialize::unserialize($data['conditions']) : []);
            $data['actions'] = (isset($data['actions']) ? Serialize::unserialize($data['actions']) : []);

            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('target with id ' . $this->model->getId() . " doesn't exist");
        }
    }

    /**
     * @param string $name
     *
     * @throws \Exception
     */
    public function getByName($name = null)
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $data = $this->db->fetchAll('SELECT id FROM targeting_rules WHERE name = ?', [$this->model->getName()]);

        if (count($data) === 1) {
            $this->getById($data[0]['id']);
        } else {
            throw new \Exception('target with name ' . $this->model->getId() . " doesn't exist or isn't unique");
        }
    }

    /**
     * Save object to database
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('targeting_rules', ['id' => $this->model->getId()]);
    }

    /**
     * @throws \Exception
     */
    public function update()
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

    public function create()
    {
        $this->db->insert('targeting_rules', []);
        $this->model->setId($this->db->lastInsertId());
    }
}
