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
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Objectbrick;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool\Serialize; 
use Pimcore\File; 

class Definition extends Model\Object\Fieldcollection\Definition {

    /**
     * @var array()
     */
    public $classDefinitions = array();

    /**
     * @var array
     */
    private $oldClassDefinitions = array();

    /**
     * @param $classDefinitions
     * @return void
     */
    public function setClassDefinitions($classDefinitions) {
        $this->classDefinitions = $classDefinitions;
        return $this;
    }

    /**
     * @return array()
     */
    public function getClassDefinitions() {
        return $this->classDefinitions;
    }

    /**
     * @static
     * @throws \Exception
     * @param $key
     * @return mixed
     */
    public static function getByKey ($key) {

        $brick = null;
        $cacheKey = "objectbrick_" . $key;

        try {
            $brick = \Zend_Registry::get($cacheKey);
            if(!$brick) {
                throw new \Exception("ObjectBrick in Registry is not valid");
            }
        } catch (\Exception $e) {
            $objectBrickFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";

            $fieldFile = $objectBrickFolder . "/" . $key . ".psf";
            if(is_file($fieldFile)) {
                $fcData = file_get_contents($fieldFile);
                $brick = Serialize::unserialize($fcData);

                \Zend_Registry::set($cacheKey, $brick);
            }
        }

        if($brick) {
            return $brick;
        }

        throw new \Exception("Object-Brick with key: " . $key . " does not exist.");
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function save () {

        if(!$this->getKey()) {
            throw new \Exception("A object-brick needs a key to be saved!");
        }

        $objectBrickFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";

        // create folder if not exist
        if(!is_dir($objectBrickFolder)) {
            File::mkdir($objectBrickFolder);
        }

        $newClassDefinitions = array();
        $classDefinitionsToDelete = array();

        foreach($this->classDefinitions as $cl) {
            if(!$cl['deleted']) {
                $newClassDefinitions[] = $cl;
            } else {
                $classDefinitionsToDelete[] = $cl;
            }
        }

        $this->classDefinitions = $newClassDefinitions;



        $serialized = Serialize::serialize($this);
        $serializedFilename = $objectBrickFolder . "/" . $this->getKey() . ".psf";


        $this->cleanupOldFiles($serializedFilename);

        File::put($serializedFilename, $serialized);

        $extendClass = "Object\\Objectbrick\\Data\\AbstractData";
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
            $extendClass = "\\" . ltrim($extendClass, "\\");
        }

        // create class

        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "/** Generated at " . date('c') . " */";
        $cd .= "\n\n";

        $cd .= "/**\n";

        if ($_SERVER["REMOTE_ADDR"]) {
            $cd .= "* IP:          " . $_SERVER["REMOTE_ADDR"] . "\n";
        }

        $cd .= "*/\n";
        $cd .= "\n\n";
        $cd .= "namespace Pimcore\\Model\\Object\\Objectbrick\\Data;";
        $cd .= "\n\n";
        $cd .= "use Pimcore\\Model\\Object;";
        $cd .= "\n\n";

        $cd .= "class " . ucfirst($this->getKey()) . " extends " . $extendClass . "  {";
        $cd .= "\n\n";

        $cd .= 'public $type = "' . $this->getKey() . "\";\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                $cd .= "public $" . $key . ";\n";
            }
        }

        $cd .= "\n\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {

                /**
                 * @var $def Object\ClassDefinition\Data
                 */
                $cd .= $def->getGetterCodeObjectbrick($this);
                $cd .= $def->getSetterCodeObjectbrick($this);
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        $fieldClassFolder = PIMCORE_CLASS_DIRECTORY . "/Object/Objectbrick/Data";
        if(!is_dir($fieldClassFolder)) {
            File::mkdir($fieldClassFolder);
        }

        $fieldClassFile = $fieldClassFolder . "/" . ucfirst($this->getKey()) . ".php";
        File::put($fieldClassFile,$cd);

        $this->createContainerClasses();
        $this->updateDatabase();
    }


    /**
     * @param $serializedFilename
     * @return void
     */
    private function cleanupOldFiles($serializedFilename) {
        $this->oldClassDefinitions = array();
        if(file_exists($serializedFilename)) {
            $prevSerialized = file_get_contents($serializedFilename);
        }

        $oldObject = Serialize::unserialize($prevSerialized);

        if(!empty($oldObject->classDefinitions)) {
            foreach($oldObject->classDefinitions as $cl) {
                $this->oldClassDefinitions[$cl['classname']] = $cl['classname'];
                $class = Object\ClassDefinition::getById($cl['classname']);
                if($class) {
                    $path = $this->getContainerClassFolder($class->getName());
                    @unlink($path . "/" . ucfirst($cl['fieldname'] . ".php"));


                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if($idx !== false) {
                                array_splice($allowedTypes, $idx, 1);
                            }
                            $fieldDef->setAllowedTypes($allowedTypes);
                        }
                    }

                    $class->save();
                }

            }
        }
    }

    /**
     * @return void
     */
    private function updateDatabase() {

        $processedClasses = array();
        if(!empty($this->classDefinitions)) {
            foreach($this->classDefinitions as $cl) {
                unset($this->oldClassDefinitions[$cl['classname']]);

                if(!$processedClasses[$cl['classname']]) {
                    $class = Object\ClassDefinition::getById($cl['classname']);
                    $this->getResource()->createUpdateTable($class);
                    $processedClasses[$cl['classname']] = true;
                }

            }
        }

        if(!empty($this->oldClassDefinitions)) {
            foreach($this->oldClassDefinitions as $cl) {
                $class = Object\ClassDefinition::getById($cl);
                if($class) {
                    $this->getResource()->delete($class);

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if($idx !== false) {
                                array_splice($allowedTypes, $idx, 1);
                            }
                            $fieldDef->setAllowedTypes($allowedTypes);
                        }
                    }

                    $class->save();
                }
            }
        }

    }

    /**
     * @throws \Exception
     */
    private function createContainerClasses() {
        $containerDefinition = array();

        if(!empty($this->classDefinitions)) {
            foreach($this->classDefinitions as $cl) {
                $containerDefinition[$cl['classname']][$cl['fieldname']][] = $this->key;

                $class = Object\ClassDefinition::getById($cl['classname']);

                $fd = $class->getFieldDefinition($cl['fieldname']);
                if (!$fd) {
                    throw new \Exception("Coult not resolve field definition for " . $cl['fieldname']);
                }
                $allowedTypes = $fd->getAllowedTypes();
                if(!in_array($this->key, $allowedTypes)) {
                    $allowedTypes[] = $this->key;
                }
                $fd->setAllowedTypes($allowedTypes);
                $class->save();

            }
        }

        $list = new Object\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach($list as $def) {
            if($this->key != $def->getKey()) {
                $classDefinitions = $def->getClassDefinitions();
                if(!empty($classDefinitions)) {
                    foreach($classDefinitions as $cl) {
                        $containerDefinition[$cl['classname']][$cl['fieldname']][] = $def->getKey();
                    }
                }
            }
        }


        foreach($containerDefinition as $classId => $cd) {
            $class = Object\ClassDefinition::getById($classId);

            if(!$class) {
                continue;
            }

            foreach($cd as $fieldname => $brickKeys) {
                $className = $this->getContainerClassName($class->getName(), $fieldname);
                $namespace = $this->getContainerNamespace($class->getName(), $fieldname);

                $cd = '<?php ';

                $cd .= "\n\n";
                $cd .= "namespace " . $namespace . ";";
                $cd .= "\n\n";
                $cd .= "class " . $className . " extends \\Pimcore\\Model\\Object\\Objectbrick {";
                $cd .= "\n\n";

                $cd .= "\n\n";
                $cd .= 'protected $brickGetters = array(' . "'" . implode("','", $brickKeys) . "');\n";
                $cd .= "\n\n";

                foreach($brickKeys as $brickKey) {
                    $cd .= 'public $' . $brickKey . " = null;\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @return \\Pimcore\\Model\\Object\\Objectbrick\\Data\\' . $brickKey . "\n";
                    $cd .= '*/' . "\n";
                    $cd .= "public function get" . ucfirst($brickKey) . "() { \n";

                    if($class->getAllowInherit()) {
                        $cd .= "\t" . 'if(!$this->' . $brickKey . ' && \\Pimcore\\Model\\Object\\AbstractObject::doGetInheritedValues($this->getObject())) { ' . "\n";
                        $cd .= "\t\t" . '$brick = $this->getObject()->getValueFromParent("' . $fieldname . '");' . "\n";
                        $cd .= "\t\t" . 'if(!empty($brick)) {' . "\n";
                        $cd .= "\t\t\t" . 'return $this->getObject()->getValueFromParent("' . $fieldname . '")->get' . ucfirst($brickKey) . "(); \n";
                        $cd .= "\t\t" . "}\n";
                        $cd .= "\t" . "}\n";
                    }
                    $cd .= '   return $this->' . $brickKey . "; \n";

                    $cd .= "}\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @param \\Pimcore\\Model\\Object\\Objectbrick\\Data\\' . $brickKey . ' $' . $brickKey . "\n";
                    $cd .= "* @return void\n";
                    $cd .= '*/' . "\n";
                    $cd .= "public function set" . ucfirst($brickKey) . " (" . '$' . $brickKey . ") {\n";
                    $cd .= "\t" . '$this->' . $brickKey . " = " . '$' . $brickKey . ";\n";
                    $cd .= "\t" . 'return $this;' . ";\n";
                    $cd .= "}\n\n";

                }

                $cd .= "}\n";
                $cd .= "\n";

                $folder = $this->getContainerClassFolder($class->getName());
                if(!is_dir($folder)) {
                    File::mkdir($folder);
                }

                $file = $folder . "/" . ucfirst($fieldname) . ".php";
                File::put($file,$cd);
            }
        }

    }

    /**
     * @param $classname
     * @param $fieldname
     * @return string
     */
    private function getContainerClassName($classname, $fieldname) {
        return ucfirst($fieldname);
    }

    /**
     * @param $classname
     * @param $fieldname
     * @return string
     */
    private function getContainerNamespace($classname, $fieldname) {
        return "Pimcore\\Model\\Object\\" . ucfirst($classname);
    }

    /**
     * @param $classname
     * @return string
     */
    private function getContainerClassFolder($classname) {
        return PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($classname);
    }

    /**
     * @return void
     */
    public function delete () {
        $fieldCollectionFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";
        $fieldFile = $fieldCollectionFolder . "/" . $this->getKey() . ".psf";

        @unlink($fieldFile);

        $fieldClassFolder = PIMCORE_CLASS_DIRECTORY . "/Object/Objectbrick/Data";
        $fieldClass = $fieldClassFolder . "/" . ucfirst($this->getKey()) . ".php";

        @unlink($fieldClass);


        $processedClasses = array();
        if(!empty($this->classDefinitions)) {
            foreach($this->classDefinitions as $cl) {
                unset($this->oldClassDefinitions[$cl['classname']]);

                if(!$processedClasses[$cl['classname']]) {
                    $class = Object\ClassDefinition::getById($cl['classname']);
                    $this->getResource()->delete($class);
                    $processedClasses[$cl['classname']] = true;


                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if($idx !== false) {
                                array_splice($allowedTypes, $idx, 1);
                            }
                            $fieldDef->setAllowedTypes($allowedTypes);
                        }
                    }

                    $class->save();

                }

            }
        }

        // update classes
        $classList = new Object\ClassDefinition\Listing();
        $classes = $classList->load();
        if(is_array($classes)){
            foreach($classes as $class){
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                        if(in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                            break;
                        }
                    }
                }
            }
        }
    }
}