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
 * @package    Object_Fieldcollection
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick_Definition extends Object_Fieldcollection_Definition {

    /**
     * @var array()
     */
    public $classDefinitions;

    public function setClassDefinitions($classDefinitions) {
        $this->classDefinitions = $classDefinitions;
    }

    public function getClassDefinitions() {
        return $this->classDefinitions;
    }


//    /**
//     * @var string
//     */
//    public $key;
//
//    /**
//     * @var string
//     */
//    public $parentClass;
//
//    /**
//     * @var array
//     */
//    public $layoutDefinitions;
//
//
//    /**
//     * @return string
//     */
//    public function getKey() {
//        return $this->key;
//    }
//
//    /**
//     * @param string $key
//     * @return void
//     */
//    public function setKey($key) {
//        $this->key = $key;
//    }
//
//    /**
//     * @return string
//     */
//    public function getParentClass() {
//        return $this->parentClass;
//    }
//
//    /**
//     * @param string $parentClass
//     * @return void
//     */
//    public function setParentClass($parentClass) {
//        $this->parentClass = $parentClass;
//    }
//
//    /**
//     * @return array
//     */
//    public function getLayoutDefinitions() {
//        return $this->layoutDefinitions;
//    }
//
//    /**
//     * @param array $layoutDefinitions
//     * @return void
//     */
//    public function setLayoutDefinitions($layoutDefinitions) {
//        $this->layoutDefinitions = $layoutDefinitions;
//
//        $this->fieldDefinitions = array();
//        $this->extractDataDefinitions($this->layoutDefinitions);
//    }
//
//    /**
//     * @return array
//     */
//    public function getFieldDefinitions() {
//        return $this->fieldDefinitions;
//    }
//
//    /**
//     * @param array $fieldDefinitions
//     * @return void
//     */
//    public function setFieldDefinitions($fieldDefinitions) {
//        $this->fieldDefinitions = $fieldDefinitions;
//    }
//
//    /**
//     * @param string $key
//     * @param Object_Class_Data $data
//     * @return void
//     */
//    public function setFieldDefinition($key, $data) {
//        $this->fieldDefinitions[$key] = $data;
//    }
//
//    /**
//     * @return Object_Data
//     */
//    public function getFieldDefinition($key) {
//
//        if (array_key_exists($key, $this->fieldDefinitions)) {
//            return $this->fieldDefinitions[$key];
//        }
//        return false;
//    }
//
//    /**
//     * @param array|Object_Class_Layout|Object_Class_Data $def
//     * @return void
//     */
//    public function extractDataDefinitions($def) {
//
//        if ($def instanceof Object_Class_Layout) {
//            if ($def->hasChilds()) {
//                foreach ($def->getChilds() as $child) {
//                    $this->extractDataDefinitions($child);
//                }
//            }
//        }
//
//        if ($def instanceof Object_Class_Data) {
//            $this->setFieldDefinition($def->getName(), $def);
//        }
//    }
    
    
    public static function getByKey ($key) {
        $objectBrickFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";
        
        $fieldFile = $objectBrickFolder . "/" . $key . ".psf";
        if(is_file($fieldFile)) {
            $fcData = file_get_contents($fieldFile);
            $fc = unserialize($fcData);
            
            return $fc;
        }
        
        throw new Exception("Object-Brick with key: " . $key . " does not exist.");
    }
    
    public function save () {

        if(!$this->getKey()) {
            throw new Exception("A object-brick needs a key to be saved!");
        }

        $objectBrickFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";

        // create folder if not exist
        if(!is_dir($objectBrickFolder)) {
            mkdir($objectBrickFolder);
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



        $serialized = serialize($this);
        $serializedFilename = $objectBrickFolder . "/" . $this->getKey() . ".psf";


        $this->cleanupOldFiles($serializedFilename);


        file_put_contents($serializedFilename, $serialized);

        $extendClass = "Object_Objectbrick_Data_Abstract";
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
        }

        // create class

        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "class Object_Objectbrick_Data_" . ucfirst($this->getKey()) . " extends " . $extendClass . "  {";
        $cd .= "\n\n";

        $cd .= 'public $type = "' . $this->getKey() . "\";\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                $cd .= "public $" . $key . ";\n";
            }
        }

        $cd .= "\n\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            $relationTypes = array();
            foreach ($this->getFieldDefinitions() as $key => $def) {

                $cd .= '/**' . "\n";
                $cd .= '* @return ' . $def->getPhpdocType() . "\n";
                $cd .= '*/' . "\n";
                $cd .= "public function get" . ucfirst($key) . " () {\n";
                $cd .= "\t return " . '$this->' . $key . ";\n";
                $cd .= "}\n\n";

                $cd .= '/**' . "\n";
                $cd .= '* @param ' . $def->getPhpdocType() . ' $' . $key . "\n";
                $cd .= "* @return void\n";
                $cd .= '*/' . "\n";
                $cd .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";
                $cd .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
                $cd .= "}\n\n";

            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        $fieldClassFolder = PIMCORE_CLASS_DIRECTORY . "/Object/Objectbrick/Data";
        if(!is_dir($fieldClassFolder)) {
            mkdir($fieldClassFolder,0755,true);
        }

        file_put_contents($fieldClassFolder . "/" . ucfirst($this->getKey()) . ".php",$cd);

        $this->createContainerClasses();
        $this->updateDatabase(); 
    }

    private $oldClassDefinitions = array();
    private function cleanupOldFiles($serializedFilename) {
        $this->oldClassDefinitions = array();
        if(file_exists($serializedFilename)) {
            $prevSerialized = file_get_contents($serializedFilename);
        }

        $oldObject = unserialize($prevSerialized);

        if(!empty($oldObject->classDefinitions)) {
            foreach($oldObject->classDefinitions as $cl) {
                $this->oldClassDefinitions[$cl['classname']] = $cl['classname'];
                $class = Object_Class::getById($cl['classname']);
                $path = $this->getContainerClassFolder($class->getName());
                @unlink($path . "/" . ucfirst($cl['fieldname'] . ".php"));
            }
        }
    }

    private function updateDatabase() {

        $processedClasses = array(); 
        if(!empty($this->classDefinitions)) {
            foreach($this->classDefinitions as $cl) {
                unset($this->oldClassDefinitions[$cl['classname']]);

                if(!$processedClasses[$cl['classname']]) {
                    $class = Object_Class::getById($cl['classname']);
                    $this->getResource()->createUpdateTable($class);
                    $processedClasses[$cl['classname']] = true;
                }

            }
        }

        if(!empty($this->oldClassDefinitions)) {
            foreach($this->oldClassDefinitions as $cl) {
                $class = Object_Class::getById($cl);
                $this->getResource()->delete($class);
            }
        }

    }

    private function createContainerClasses() {
        $containerDefinition = array();

        if(!empty($this->classDefinitions)) {
            foreach($this->classDefinitions as $cl) {
                $containerDefinition[$cl['classname']][$cl['fieldname']][] = $this->key;
            }
        }

        $list = new Object_Objectbrick_Definition_List();
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
           $class = Object_Class::getById($classId);

            foreach($cd as $fieldname => $brickKeys) {
                $className = $this->getContainerClassName($class->getName(), $fieldname);

                $cd = '<?php ';

                $cd .= "\n\n";
                $cd .= "class " . $className . " extends Object_Objectbrick {";
                $cd .= "\n\n";

                $cd .= "\n\n";

                foreach($brickKeys as $brickKey) {
                    $cd .= 'public $' . $brickKey . " = null;\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @return Object_Objectbrick_Data_' . $brickKey . "\n";
                    $cd .= '*/' . "\n";
                    $cd .= "public function get" . ucfirst($brickKey) . "() { \n";

                    if($class->getAllowInherit()) {
                        $cd .= '   if(!$this->' . $brickKey . " && Object_Abstract::doGetInheritedValues()) { \n";
                        $cd .= '      return $this->object->getValueFromParent("' . $fieldname . '")->get' . ucfirst($brickKey) . "(); \n";
                        $cd .= "   }\n";
                    }
                    $cd .= '   return $this->' . $brickKey . "; \n";

                    $cd .= "}\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @param Object_Objectbrick_Data_' . $brickKey . ' $' . $brickKey . "\n";
                    $cd .= "* @return void\n";
                    $cd .= '*/' . "\n";
                    $cd .= "public function set" . ucfirst($brickKey) . " (" . '$' . $brickKey . ") {\n";
                    $cd .= "\t" . '$this->' . $brickKey . " = " . '$' . $brickKey . ";\n";
                    $cd .= "}\n\n";

                }

                $cd .= "}\n";
                $cd .= "\n";

                $folder = $this->getContainerClassFolder($class->getName());
                if(!is_dir($folder)) {
                    mkdir($folder,0755,true);
                }

                file_put_contents($folder . "/" . ucfirst($fieldname) . ".php",$cd);
            }
        }

    }

    private function getContainerClassName($classname, $fieldname) {
        return "Object_" . ucfirst($classname) . "_" . ucfirst($fieldname);
    }
    private function getContainerClassFolder($classname) {
        return PIMCORE_CLASS_DIRECTORY . "/Object/" . ucfirst($classname);
    }
    
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
                    $class = Object_Class::getById($cl['classname']);
                    $this->getResource()->delete($class);
                    $processedClasses[$cl['classname']] = true;
                }

            }
        }
        
    }

}
