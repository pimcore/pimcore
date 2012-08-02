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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class extends Pimcore_Model_Abstract {

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
     * @var array
     */
    public $fieldDefinitions;

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
     * @param integer $id
     * @return Object_Class
     */
    public static function getById($id) {

        $cacheKey = "class_" . $id;

        try {
            $class = Zend_Registry::get($cacheKey);
            if(!$class){
                throw new Exception("Class in registry is null");
            }
        }
        catch (Exception $e) {

            try {
                $class = new self();
                Zend_Registry::set($cacheKey, $class);
                $class->getResource()->getById($id);
            } catch (Exception $e) {
                Logger::error($e);
                return null;
            }
        }

        return $class;
    }

    /**
     * @param string $name
     * @return Object_Class
     */
    public static function getByName($name) {
        $class = new self();

        try {
            $class->getResource()->getByName($name);
        } catch (Exception $e) {
            Logger::error($e);
            return null;
        }

        // to have a singleton in a way. like all instances of Element_Interface do also, like Object_Abstract
        if($class->getId() > 0) {
            return self::getById($class->getId());
        }
    }

    /**
     * @param array $values
     * @return Object_Class
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
     * @return void
     */
    public function save() {

        $this->setModificationDate(time());

        $this->getResource()->save();

        // create class for object
        $extendClass = "Object_Concrete";
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
        }

        // creaste directory if not exists
        if (!is_dir(PIMCORE_CLASS_DIRECTORY . "/Object")) {
            mkdir(PIMCORE_CLASS_DIRECTORY . "/Object");
        }

        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "class Object_" . ucfirst($this->getName()) . " extends " . $extendClass . " {";
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
        $cd .= '* @return Object_' . ucfirst($this->getName()) . "\n";
        $cd .= '*/' . "\n";
        $cd .= 'public static function create($values = array()) {';
        $cd .= "\n";
        $cd .= "\t" . '$object = new self();' . "\n";
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
            throw new Exception("Cannot write class file in " . $classFile . " please check the rights on this directory");
        }
        file_put_contents($classFile,$cd);
        chmod($classFile, 0766);

        // create list class

        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "class Object_" . ucfirst($this->getName()) . "_List extends Object_List_Concrete {";
        $cd .= "\n\n";

        $cd .= 'public $classId = ' . $this->getId() . ";\n";
        $cd .= 'public $className = "' . $this->getName() . '"' . ";\n";

        $cd .= "\n\n";
        $cd .= "}\n";
        /*$cd .= "?>";*/


        @mkdir(PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()));

        $classListFile = PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($this->getName()) . "/List.php";
        if(!is_writable(dirname($classListFile)) || (is_file($classListFile) && !is_writable($classListFile))) {
            throw new Exception("Cannot write class file in " . $classListFile . " please check the rights on this directory");
        }
        file_put_contents($classListFile,$cd);
        chmod($classListFile, 0766);

        // empty object cache
        try {
            Pimcore_Model_Cache::clearTag("class_" . $this->getId());
        }
        catch (Exception $e) {
        }
    }

    /**
     * @return void
     */
    public function delete() {

        // delete all objects using this class
        $list = new Object_List();
        $list->setCondition("o_classId = ?", $this->getId());
        $list->load();

        foreach ($list->getObjects() as $o) {
            $o->delete();
        }

        $this->deletePhpClasses();
        
        // empty object cache
        try {
            Pimcore_Model_Cache::clearTag("class_" . $this->getId());
        }
        catch (Exception $e) {}
        
        // empty output cache
        try {
            Pimcore_Model_Cache::clearTag("output");
        }
        catch (Exception $e) {}
        
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
    function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param string $name
     * @return void
     */
    function setName($name) {
        $this->name = $name;
    }

    /**
     * @param int $creationDate
     * @return void
     */
    function setCreationDate($creationDate) {
        $this->creationDate = (int) $creationDate;
    }

    /**
     * @param int $modificationDate
     * @return void
     */
    function setModificationDate($modificationDate) {
        $this->modificationDate = (int) $modificationDate;
    }

    /**
     * @param int $userOwner
     * @return void
     */
    function setUserOwner($userOwner) {
        $this->userOwner = (int) $userOwner;
    }

    /**
     * @param int $userModification
     * @return void
     */
    function setUserModification($userModification) {
        $this->userModification = (int) $userModification;
    }

    /**
     * @return Object_Class_Data[]
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
    }

    /**
     * @param string $key
     * @param Object_Class_Data $data
     * @return void
     */
    public function setFieldDefinition($key, $data) {
        $this->fieldDefinitions[$key] = $data;
    }

    /**
     * @return Object_Data
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
    }

    /**
     * @param array|Object_Class_Layout|Object_Class_Data $def
     * @return void
     */
    public function extractDataDefinitions($def) {

        if ($def instanceof Object_Class_Layout) {
            if ($def->hasChilds()) {
                foreach ($def->getChilds() as $child) {
                    $this->extractDataDefinitions($child);
                }
            }
        }

        if ($def instanceof Object_Class_Data) {
            $this->setFieldDefinition($def->getName(), $def);
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
    }

    /**
     * @param boolean $allowInherit
     * @return void
     */
    public function setAllowInherit($allowInherit) {
        $this->allowInherit = (bool) $allowInherit;
    }

    /**
     * @param boolean $allowVariants
     * @return void
     */
    public function setAllowVariants($allowVariants) {
        $this->allowVariants = (bool) $allowVariants;
    }

    /**
     * @return string
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon) {
        $this->icon = $icon;
    }
    
    /**
     * @return array
     */
    public function getPropertyVisibility() {
        return $this->propertyVisibility;
    }

    /**
     * @param array $propertyVisibility
     */
    public function setPropertyVisibility($propertyVisibility) {
        if(is_array($propertyVisibility)) {
            $this->propertyVisibility = $propertyVisibility;
        }
    }

    /**
     * @param string $previewUrl
     */
    public function setPreviewUrl($previewUrl)
    {
        $this->previewUrl = $previewUrl;
    }

    /**
     * @return string
     */
    public function getPreviewUrl()
    {
        return $this->previewUrl;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


}
