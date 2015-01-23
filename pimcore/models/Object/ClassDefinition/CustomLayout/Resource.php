<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\CustomLayout;

use Pimcore\Model;
use Pimcore\File; 
use Pimcore\Tool\Serialize; 

class Resource extends Model\Resource\AbstractResource {

    /**
     * @var Model\Object\ClassDefinition\CustomLayout
     */
    protected $model;

    /**
     * @var array
     */
    protected $_sqlChangeLog = array();
    
    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("custom_layouts");
    }

    /**
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {
        if (!$id) {
            $id = $this->model->getId();
        }

        $layoutRaw = $this->db->fetchRow("SELECT * FROM custom_layouts WHERE id = ?", $id);

        if($layoutRaw["id"]) {
            $this->assignVariablesToModel($layoutRaw);

            $this->model->setLayoutDefinitions($this->getLayoutData());
        } else {
            throw new \Exception("Layout with ID " . $id . " doesn't exist");
        }
    }


    
    /**
     * Save object to database
     *
     * @return mixed
     */
    protected function getLayoutData () {
        $file = PIMCORE_CUSTOMLAYOUT_DIRECTORY . "/custom_definition_". $this->model->getId() .".psf";
        if(is_file($file)) {
            return Serialize::unserialize(file_get_contents($file));
        }
        return;
    }


    /**
     * Save layout to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function update() {

        $class = get_object_vars($this->model);
        $data = array();

        foreach ($class as $key => $value) {
            if (in_array($key, $this->validColumns)) {

                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                } else  if(is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update("custom_layouts", $data, $this->db->quoteInto("id = ?", $this->model->getId()));

         // save definition as a serialized file
        $definitionFile = PIMCORE_CUSTOMLAYOUT_DIRECTORY."/custom_definition_". $this->model->getId() .".psf";
        if(!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new \Exception("Cannot write definition file in: " . $definitionFile . " please check write permission on this directory.");
        }
        File::put($definitionFile, Serialize::serialize($this->model->layoutDefinitions));
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $this->db->insert("custom_layouts", array("name" => $this->model->getName(), "classId" => $this->model->getClassId()));

        $this->model->setId($this->db->lastInsertId());
        $this->model->setCreationDate(time());
        $this->model->setModificationDate(time());

        $this->save();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {

        $this->db->delete("custom_layouts", $this->db->quoteInto("id = ?", $this->model->getId()));

        @unlink(PIMCORE_CUSTOMLAYOUT_DIRECTORY."/custom_definition_". $this->model->getId() .".psf");
    }

}
