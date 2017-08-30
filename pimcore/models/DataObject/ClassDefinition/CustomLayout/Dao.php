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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\File;
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
     * @var array
     */
    protected $_sqlChangeLog = [];

    /**
     * @param null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if (!$id) {
            $id = $this->model->getId();
        }

        $layoutRaw = $this->db->fetchRow('SELECT * FROM custom_layouts WHERE id = ?', $id);

        if ($layoutRaw['id']) {
            $this->assignVariablesToModel($layoutRaw);

            $this->model->setLayoutDefinitions($this->getLayoutData());
        } else {
            throw new \Exception('Layout with ID ' . $id . " doesn't exist");
        }
    }

    /**
     * Save object to database
     *
     * @return string|null
     */
    protected function getLayoutData()
    {
        $file = PIMCORE_CUSTOMLAYOUT_DIRECTORY . '/custom_definition_'. $this->model->getId() .'.psf';
        if (is_file($file)) {
            return Serialize::unserialize(file_get_contents($file));
        }

        return;
    }

    /**
     * Save layout to database
     *
     * @return bool
     *
     * @todo: update() and create() don't return anything
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->update();
        }

        return $this->create();
    }

    /**
     * @throws \Exception
     * @throws \Exception
     */
    public function update()
    {
        $class = get_object_vars($this->model);
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

        // save definition as a serialized file
        $definitionFile = PIMCORE_CUSTOMLAYOUT_DIRECTORY.'/custom_definition_'. $this->model->getId() .'.psf';
        if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new \Exception('Cannot write definition file in: ' . $definitionFile . ' please check write permission on this directory.');
        }
        File::put($definitionFile, Serialize::serialize($this->model->layoutDefinitions));
    }

    /**
     * Create a new record for the object in database
     */
    public function create()
    {
        $this->db->insert('custom_layouts', ['name' => $this->model->getName(), 'classId' => $this->model->getClassId()]);

        $this->model->setId($this->db->lastInsertId());
        $this->model->setCreationDate(time());
        $this->model->setModificationDate(time());

        $this->save();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('custom_layouts', ['id' => $this->model->getId()]);
        @unlink(PIMCORE_CUSTOMLAYOUT_DIRECTORY.'/custom_definition_'. $this->model->getId() .'.psf');
    }
}
