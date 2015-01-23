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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Cache;

class CustomLayout extends Model\AbstractModel {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $userOwner;

    /**
     * @var int
     */
    public $userModification;

    /**
     * @var int
     */
    public $classId;

    /**
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @var int
     */
    public $default;

    /**
     * @param $id
     * @return mixed|null|CustomLayout
     * @throws \Exception
     */
    public static function getById($id) {
        if($id === null) {
            throw new \Exception("CustomLayout id is null");
        }

        $cacheKey = "customlayout_" . $id;

        try {
            $customLayout = \Zend_Registry::get($cacheKey);
            if(!$customLayout){
                throw new \Exception("Custom Layout in registry is null");
            }
        } catch (\Exception $e) {
            try {
                $customLayout = new self();
                $customLayout->getResource()->getById($id);

                Object\Service::synchronizeCustomLayout($customLayout);
                \Zend_Registry::set($cacheKey, $customLayout);
            } catch (\Exception $e) {
                \Logger::error($e);
                return null;
            }
        }

        return $customLayout;
    }


    /**
     * @param array $values
     * @return CustomLayout
     */
    public static function create($values = array()) {
        $class = new self();
        $class->setValues($values);
        return $class;
    }

    /**
     * @return void
     */
    public function save() {

        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.customLayout.preUpdate", $this);

        } else {
            \Pimcore::getEventManager()->trigger("object.customLayout.preAdd", $this);
        }

        $this->setModificationDate(time());


        // create directory if not exists
        if (!is_dir(PIMCORE_CUSTOMLAYOUT_DIRECTORY)) {
            \Pimcore\File::mkdir(PIMCORE_CUSTOMLAYOUT_DIRECTORY);
        }

        $this->getResource()->save();

        // empty custom layout cache
        try {
            Cache::clearTag("customlayout_" . $this->getId());
        }
        catch (\Exception $e) {
        }
    }

    /**
     * @return void
     */
    public function delete() {
        // empty object cache
        try {
            Cache::clearTag("customlayout_" . $this->getId());
        }
        catch (\Exception $e) {}

        // empty output cache
        try {
            Cache::clearTag("output");
        }
        catch (\Exception $e) {}

        $this->getResource()->delete();
    }


    /**
     * @return int
     */
    function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    function getName() {
        return $this->name;
    }

    /**
     * @return int
     */
    function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * @return int
     */
    function getModificationDate() {
        return $this->modificationDate;
    }

    /**
     * @return int
     */
    function getUserOwner() {
        return $this->userOwner;
    }

    /**
     * @return int
     */
    function getUserModification() {
        return $this->userModification;
    }

    /**
     * @param int $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param int $default
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = (int)$default;
        return $this;
    }



    /**
     * @param int $creationDate
     * @return void
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @param int $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @param int $userOwner
     * @return void
     */
    public function setUserOwner($userOwner) {
        $this->userOwner = (int) $userOwner;
        return $this;
    }

    /**
     * @param int $userModification
     * @return void
     */
    public function setUserModification($userModification) {
        $this->userModification = (int) $userModification;
        return $this;
    }


    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $layoutDefinitions
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;
    }

    /**
     * @return array
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param int $classId
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
    }

    /**
     * @return int
     */
    public function getClassId()
        {
        return $this->classId;
    }
}
