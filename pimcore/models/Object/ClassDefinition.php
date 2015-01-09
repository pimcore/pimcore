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

namespace Pimcore\Model\Object;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\File;
use Pimcore\Model\Cache; 

class ClassDefinition extends Model\AbstractModel {

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
     * Name of the parent class if set
     *
     * @var string
     */
    public $parentClass;

    /**
     * @var boolean
     */
    public $allowInherit = false;

    /**
     * @var boolean
     */
    public $allowVariants = false;

    /**
     * @var boolean
     */
    public $showVariants = false;

    /**
     * @var array
     */
    public $fieldDefinitions = array();

    /**
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $previewUrl;

    /**
     * @var array
     */
    public $propertyVisibility = array(
        "grid" => array(
            "id" => true,
            "path" => true,
            "published" => true,
            "modificationDate" => true,
            "creationDate" => true
        ),
        "search" => array(
            "id" => true,
            "path" => true,
            "published" => true,
            "modificationDate" => true,
            "creationDate" => true
        )
    );


    /**
     * @param $id
     * @return mixed|null|ClassDefinition
     * @throws \Exception
     */
    public static function getById($id) {

        if($id === null) {
            throw new \Exception("Class id is null");
        }

        $cacheKey = "class_" . $id;

        try {
            $class = \Zend_Registry::get($cacheKey);
            if(!$class){
                throw new \Exception("Class in registry is null");
            }
        }
        catch (\Exception $e) {

            try {
                $class = new self();
                $class->getResource()->getById($id);
                \Zend_Registry::set($cacheKey, $class);
            } catch (\Exception $e) {
                \Logger::error($e);
                return null;
            }
        }

        return $class;
    }

    /**
     * @param string $name
     * @return self
     */
    public static function getByName($name) {
        $class = new self();

        try {
            $class->getResource()->getByName($name);
        } catch (\Exception $e) {
            \Logger::error($e);
            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like Object\AbstractObject
        if($class->getId() > 0) {
            return self::getById($class->getId());
        }
    }

    /**
     * @param array $values
     * @return self
     */
    public static function create($values = array()) {
        $class = new self();
        $class->setValues($values);
        return $class;
    }

    /**
     * @param string $name
     * @return void
     */
    public function rename($name) {

        $this->deletePhpClasses();
        $this->updateClassNameInObjects($name);

        $this->setName($name);
        $this->save();
    }


    /**
     * @throws \Exception
     */
    public function save() {

        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.class.preUpdate", $this);

        } else {
            \Pimcore::getEventManager()->trigger("object.class.preAdd", $this);
        }

        $this->setModificationDate(time());

        $this->getResource()->save();

        // create class for object
        $extendClass = "Concrete";
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
            $extendClass = "\\" . ltrim($extendClass, "\\");
        }

        // creaste directory if not exists
        if (!is_dir(PIMCORE_CLASS_DIRECTORY . "/Object")) {
            File::mkdir(PIMCORE_CLASS_DIRECTORY . "/Object");
        }

        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "/** Generated at " . date('c') . " */";
        $cd .= "\n\n";

        $cd .= "/**\n";

        if ($this->getDescription()) {
            $description = str_replace(array("/**", "*/", "//"), "", $this->getDescription());
            $description = str_replace("\n", "\n* ", $description);

            $cd .= "* " . $description . "\n";
        }

        $cd .= "* Inheritance: " . ($this->getAllowInherit() ? "yes" : "no") . "\n";
        $cd .= "* Variants   : " . ($this->getAllowVariants() ? "yes" : "no") . "\n";

        $user = Model\User::getById($this->getUserModification());
        if ($user) {
            $cd .= "* Changed by : " . $user->getName() . " (" . $user->getId() . ")" . "\n";
        }

        if ($_SERVER["REMOTE_ADDR"]) {
            $cd .= "* IP:          " . $_SERVER["REMOTE_ADDR"] . "\n";
        }

        $cd .= "*/\n";

        $cd .= "\n\n";
        $cd .= "namespace Pimcore\\Model\\Object;";
        $cd .= "\n\n";
        $cd .= "\n\n";

        $cd .= "class " . ucfirst($this->getName()) . " extends " . $extendClass . " {";
        $cd .= "\n\n";

        $cd .= 'public $o_classId = ' . $this->getId() . ";\n";
        $cd .= 'public $o_className = "' . $this->getName() . '"' . ";\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                if (!(method_exists($def,"isRemoteOwner") and $def->isRemoteOwner())) {
                    $cd .= "public $" . $key . ";\n";
                }
            }
        }

        $cd .= "\n\n";


        $cd .= '/**' . "\n";
        $cd .= '* @param array $values' . "\n";
        $cd .= '* @return \\Pimcore\\Model\\Object\\' . ucfirst($this->getName()) . "\n";
        $cd .= '*/' . "\n";
        $cd .= 'public static function create($values = array()) {';
        $cd .= "\n";
        $cd .= "\t" . '$object = new static();' . "\n";
        $cd .= "\t" . '$object->setValues($values);' . "\n";
        $cd .= "\t" . 'return $object;' . "\n";
        $cd .= "}";

        $cd .= "\n\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            $relationTypes = array();
            foreach ($this->getFieldDefinitions() as $key => $def) {

                if (method_exists($def,"isRemoteOwner") and $def->isRemoteOwner()) {
                    continue;
                }

                // get setter and getter code
                $cd .= $def->getGetterCode($this);
                $cd .= $def->getSetterCode($this);

                // call the method "classSaved" if exists, this is used to create additional data tables or whatever which depends on the field definition, for example for localizedfields
                if(method_exists($def, "classSaved")) {
                    $def->classSaved($this);
                }

                if ($def->isRelationType()) {
                    $relationTypes[$key] = array("type" => $def->getFieldType());
                }

                // collect lazyloaded fields
                if (method_exists($def,"getLazyLoading") and $def->getLazyLoading()) {
                    $lazyLoadedFields[] = $key;
                }
            }

            $cd .= 'protected static $_relationFields = ' . var_export($relationTypes, true) . ";\n\n";
            $cd .= 'public $lazyLoadedFields = ' . var_export($lazyLoadedFields, true) . ";\n\n";
        }

        $cd .= "}\n";
        $cd .= "\n";

        $classFile = PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()) . ".php";
        if(!is_writable(dirname($classFile)) || (is_file($classFile) && !is_writable($classFile))) {
            throw new \Exception("Cannot write class file in " . $classFile . " please check the rights on this directory");
        }
        File::put($classFile, $cd);

        // create list class

        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "namespace Pimcore\\Model\\Object\\" . ucfirst($this->getName()) . ";";
        $cd .= "\n\n";
        $cd .= "use Pimcore\\Model\\Object;";
        $cd .= "\n\n";
        $cd .= "class Listing extends Object\\Listing\\Concrete {";
        $cd .= "\n\n";

        $cd .= 'public $classId = ' . $this->getId() . ";\n";
        $cd .= 'public $className = "' . $this->getName() . '"' . ";\n";

        $cd .= "\n\n";
        $cd .= "}\n";
        /*$cd .= "?>";*/


        File::mkdir(PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()));

        $classListFile = PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()) . "/Listing.php";
        if(!is_writable(dirname($classListFile)) || (is_file($classListFile) && !is_writable($classListFile))) {
            throw new \Exception("Cannot write class file in " . $classListFile . " please check the rights on this directory");
        }
        File::put($classListFile,$cd);

        // empty object cache
        try {
            Cache::clearTag("class_" . $this->getId());
        }
        catch (\Exception $e) {
        }
    }

    /**
     * @return void
     */
    public function delete() {

        // delete all objects using this class
        $list = new Listing();
        $list->setCondition("o_classId = ?", $this->getId());
        $list->load();

        foreach ($list->getObjects() as $o) {
            $o->delete();
        }

        $this->deletePhpClasses();

        // empty object cache
        try {
            Cache::clearTag("class_" . $this->getId());
        }
        catch (\Exception $e) {}

        // empty output cache
        try {
            Cache::clearTag("output");
        }
        catch (\Exception $e) {}

        $customLayouts = new ClassDefinition\CustomLayout\Listing();
        $customLayouts->setCondition("classId = " . $this->getId());
        $customLayouts = $customLayouts->load();

        foreach ($customLayouts as $customLayout) {
            $customLayout->delete();
        }

        $this->getResource()->delete();
    }

    /**
     * @return void
     */
    protected function deletePhpClasses() {
        // delete the class files
        @unlink(PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()) . ".php");
        @unlink(PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()) . "/List.php");
        @rmdir(PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()));
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
     * @return Object\ClassDefinition\Data[]
     */
    public function getFieldDefinitions() {
        return $this->fieldDefinitions;
    }

    /**
     * @return array
     */
    public function getLayoutDefinitions() {
        return $this->layoutDefinitions;
    }

    /**
     * @param array $fieldDefinitions
     * @return void
     */
    public function setFieldDefinitions($fieldDefinitions) {
        $this->fieldDefinitions = $fieldDefinitions;
        return $this;
    }

    /**
     * @param string $key
     * @param Object\ClassDefinition\Data $data
     * @return void
     */
    public function addFieldDefinition($key, $data) {
        $this->fieldDefinitions[$key] = $data;
        return $this;
    }

    /**
     * @return Object\ClassDefinition\Data
     */
    public function getFieldDefinition($key) {

        if (array_key_exists($key, $this->fieldDefinitions)) {
            return $this->fieldDefinitions[$key];
        }
        return false;
    }

    /**
     * @param array $layoutDefinitions
     * @return void
     */
    public function setLayoutDefinitions($layoutDefinitions) {
        $this->layoutDefinitions = $layoutDefinitions;

        $this->fieldDefinitions = array();
        $this->extractDataDefinitions($this->layoutDefinitions);

        return $this;
    }

    /**
     * @param array|Object\ClassDefinition\Layout|Object\ClassDefinition\Data $def
     * @return void
     */
    public function extractDataDefinitions($def) {

        if ($def instanceof Object\ClassDefinition\Layout) {
            if ($def->hasChilds()) {
                foreach ($def->getChilds() as $child) {
                    $this->extractDataDefinitions($child);
                }
            }
        }

        if ($def instanceof Object\ClassDefinition\Data) {
            $existing = $this->getFieldDefinition($def->getName());
            if($existing && method_exists($existing, "addReferencedField")) {
                // this is especially for localized fields which get aggregated here into one field definition
                // in the case that there are more than one localized fields in the class definition
                // see also pimcore.object.edit.addToDataFields();
                $existing->addReferencedField($def);
            } else {
                $this->addFieldDefinition($def->getName(), $def);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getParent() {
        return $this->parent;
    }



    /**
     * @param mixed $parent
     * @return void
     */
    public function setParent($parent) {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentClass() {
        return $this->parentClass;
    }

    /**
     * @return boolean
     */
    public function getAllowInherit() {
        return $this->allowInherit;
    }

    /**
     * @return boolean
     */
    public function getAllowVariants() {
        return $this->allowVariants;
    }

    /**
     * @param string $parentClass
     * @return void
     */
    public function setParentClass($parentClass) {
        $this->parentClass = $parentClass;
        return $this;
    }

    /**
     * @param boolean $allowInherit
     * @return void
     */
    public function setAllowInherit($allowInherit) {
        $this->allowInherit = (bool) $allowInherit;
        return $this;
    }

    /**
     * @param boolean $allowVariants
     * @return void
     */
    public function setAllowVariants($allowVariants) {
        $this->allowVariants = (bool) $allowVariants;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * @param $icon
     * @return $this
     */
    public function setIcon($icon) {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return array
     */
    public function getPropertyVisibility() {
        return $this->propertyVisibility;
    }

    /**
     * @param $propertyVisibility
     * @return $this
     */
    public function setPropertyVisibility($propertyVisibility) {
        if(is_array($propertyVisibility)) {
            $this->propertyVisibility = $propertyVisibility;
        }
        return $this;
    }

    /**
     * @param $previewUrl
     * @return $this
     */
    public function setPreviewUrl($previewUrl)
    {
        $this->previewUrl = $previewUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->previewUrl;
    }

    /**
     * @param $description
     * @return $this
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
     * @param boolean $showVariants
     */
    public function setShowVariants($showVariants)
    {
        $this->showVariants = (bool) $showVariants;
    }

    /**
     * @return boolean
     */
    public function getShowVariants()
    {
        return $this->showVariants;
    }
}
