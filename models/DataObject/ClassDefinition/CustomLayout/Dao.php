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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Model;
use Pimcore\Tool\Serialize;

/**
 * @property \Pimcore\Model\DataObject\ClassDefinition\CustomLayout $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @var Model\DataObject\ClassDefinition\CustomLayout
     */
    protected $model;

    /**
     * @param string|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if (!$id) {
            $id = $this->model->getId();
        }

        $layoutRaw = $this->db->fetchRow('SELECT * FROM custom_layouts WHERE id = ?', $id);

        if (!empty($layoutRaw['id'])) {
            $this->assignVariablesToModel($layoutRaw);

            $this->model->setLayoutDefinitions($this->getLayoutData());
        } else {
            throw new \Exception('Layout with ID ' . $id . " doesn't exist");
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getIdByName($name)
    {
        $id = null;
        try {
            if (!empty($name)) {
                $id = $this->db->fetchOne('SELECT id FROM custom_layouts WHERE name = ?', $name);
            }
        } catch (\Exception $e) {
        }

        return $id;
    }

    /**
     * @param string $id
     *
     * @return string|null
     */
    public function getNameById($id)
    {
        $name = null;
        try {
            if (!empty($id)) {
                $name = $this->db->fetchOne('SELECT name FROM custom_layouts WHERE id = ?', $id);
            }
        } catch (\Exception $e) {
        }

        return $name;
    }

    /**
     * @param string $name
     * @param string $classId
     *
     * @return int|null
     */
    public function getIdByNameAndClassId($name, $classId)
    {
        $id = null;
        try {
            if (!empty($name) && !empty($classId)) {
                $id = $this->db->fetchOne('SELECT id FROM custom_layouts WHERE name = ? AND classId = ?', [$name, $classId]);
            }
        } catch (\Exception $e) {
        }

        return $id;
    }

    /**
     * @return int
     */
    public function getNewId()
    {
        $maxId = $this->db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM custom_layouts;');
        $newId = $maxId ? $maxId + 1 : 1;
        $this->model->setId($newId);

        return $newId;
    }

    /**
     * @return Model\DataObject\ClassDefinition\Layout|null
     */
    protected function getLayoutData()
    {
        $file = PIMCORE_CUSTOMLAYOUT_DIRECTORY . '/custom_definition_'. $this->model->getId() .'.php';
        if (is_file($file)) {
            $layout = @include $file;
            if ($layout instanceof Model\DataObject\ClassDefinition\CustomLayout) {
                return $layout->getLayoutDefinitions();
            }
        }

        return null;
    }

    /**
     * Get latest identifier
     *
     * @param string $classId
     *
     * @return int
     */
    public function getLatestIdentifier($classId)
    {
        $maxId = $this->db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM custom_layouts');

        return $maxId ? $maxId + 1 : 1;
    }

    /**
     * Save layout to database
     *
     * @param bool $isUpdate
     *
     * @throws \Exception
     */
    public function save($isUpdate = true)
    {
        if (!$this->model->getId()) {
            $maxId = $this->db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM custom_layouts;');
            $this->model->setId($maxId ? $maxId + 1 : 1);
        }

        if (!$isUpdate) {
            $this->create();
        } else {
            $this->update();
        }
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        $class = $this->model->getObjectVars();
        $data = [];

        foreach ($class as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('custom_layouts'))) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('custom_layouts', $data, ['id' => $this->model->getId()]);
    }

    /**
     * Create a new record for the object in database
     */
    public function create()
    {
        $this->db->insert('custom_layouts', ['id' => $this->model->getId(), 'name' => $this->model->getName(), 'classId' => $this->model->getClassId()]);

        $this->model->setCreationDate(time());
        $this->model->setModificationDate(time());

        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('custom_layouts', ['id' => $this->model->getId()]);
        @unlink(PIMCORE_CUSTOMLAYOUT_DIRECTORY.'/custom_definition_'. $this->model->getId() .'.php');
    }
}
