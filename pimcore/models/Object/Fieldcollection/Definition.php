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

class Object_Fieldcollection_Definition extends Pimcore_Model_Abstract {
    
    /**
     * @var string
     */
    public $key;
    
    /**
     * @var string
     */
    public $parentClass;
    
    /**
     * @var array
     */
    public $layoutDefinitions;
    
    
    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     * @return void
     */
    public function setKey($key) {
        $this->key = $key;
    }
    
    /**
     * @return string
     */
    public function getParentClass() {
        return $this->parentClass;
    }

    /**
     * @param string $parentClass
     * @return void
     */
    public function setParentClass($parentClass) {
        $this->parentClass = $parentClass;
    }
    
    /**
     * @return array
     */
    public function getLayoutDefinitions() {
        return $this->layoutDefinitions;
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
     * @return array
     */
    public function getFieldDefinitions() {
        return $this->fieldDefinitions;
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
    
    
    public static function getByKey ($key) {

        $fc = null;
        $cacheKey = "fieldcollection_" . $key;

        try {
            $fc = Zend_Registry::get($cacheKey);
            if(!$fc) {
                throw new Exception("FieldCollection in registry is not valid");
            }
        } catch (Exception $e) {
            $fieldCollectionFolder = PIMCORE_CLASS_DIRECTORY . "/fieldcollections";

            $fieldFile = $fieldCollectionFolder . "/" . $key . ".psf";
            if(is_file($fieldFile)) {
                $fcData = file_get_contents($fieldFile);
                $fc = Pimcore_Tool_Serialize::unserialize($fcData);

                Zend_Registry::set($cacheKey, $fc);
            }
        }

        if($fc) {
            return $fc;
        }
        
        throw new Exception("Field-Collection with key: " . $key . " does not exist.");
    }
    
    public function save () {
        
        if(!$this->getKey()) {
            throw new Exception("A field-collection needs a key to be saved!");
        }
        
        $fieldCollectionFolder = PIMCORE_CLASS_DIRECTORY . "/fieldcollections";
        
        // create folder if not exist
        if(!is_dir($fieldCollectionFolder)) {
            mkdir($fieldCollectionFolder);
        }
        
        $serialized = Pimcore_Tool_Serialize::serialize($this);

        $definitionFile = $fieldCollectionFolder . "/" . $this->getKey() . ".psf";

        if(!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new Exception("Cannot write definition file in: " . $definitionFile . " please check write permission on this directory.");
        }

        file_put_contents($definitionFile,$serialized);
        chmod($definitionFile, 0766);
        
        $extendClass = "Object_Fieldcollection_Data_Abstract";
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
        }

        
        // create class file
        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= "class Object_Fieldcollection_Data_" . ucfirst($this->getKey()) . " extends " . $extendClass . "  {";
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

                /**
                 * @var $def Object_Class_Data
                 */

                $cd .= $def->getGetterCodeFieldcollection($this);
                $cd .= $def->getSetterCodeFieldcollection($this);


//                $cd .= '/**' . "\n";
//                $cd .= '* @return ' . $def->getPhpdocType() . "\n";
//                $cd .= '*/' . "\n";
//                $cd .= "public function get" . ucfirst($key) . " () {\n";
//                $cd .= "\t return " . '$this->' . $key . ";\n";
//                $cd .= "}\n\n";
//
//                $cd .= '/**' . "\n";
//                $cd .= '* @param ' . $def->getPhpdocType() . ' $' . $key . "\n";
//                $cd .= "* @return void\n";
//                $cd .= '*/' . "\n";
//                $cd .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";
//                $cd .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
//                $cd .= "}\n\n";
            
            }
        }

        $cd .= "}\n";
        $cd .= "\n";
        
        $fieldClassFolder = PIMCORE_CLASS_DIRECTORY . "/Object/Fieldcollection/Data"; 
        if(!is_dir($fieldClassFolder)) {
            mkdir($fieldClassFolder,0766,true);
        }


        $classFile = $fieldClassFolder . "/" . ucfirst($this->getKey()) . ".php";
        if(!is_writable(dirname($classFile)) || (is_file($classFile) && !is_writable($classFile))) {
            throw new Exception("Cannot write definition file in: " . $classFile . " please check write permission on this directory.");
        }

        file_put_contents($classFile,$cd);
        chmod($classFile,0766);
        
        
        // update classes
        $classList = new Object_Class_List();
        $classes = $classList->load();
        if(is_array($classes)){
            foreach($classes as $class){
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if($fieldDef instanceof Object_Class_Data_Fieldcollections) {
                        if(in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                            $this->getResource()->createUpdateTable($class);
                            break;
                        }
                    }
                }
            }
        }
    }
    
    public function delete () {
        $fieldCollectionFolder = PIMCORE_CLASS_DIRECTORY . "/fieldcollections";
        $fieldFile = $fieldCollectionFolder . "/" . $this->getKey() . ".psf";
        
        @unlink($fieldFile);
        
        $fieldClassFolder = PIMCORE_CLASS_DIRECTORY . "/Object/Fieldcollection/Data"; 
        $fieldClass = $fieldClassFolder . "/" . ucfirst($this->getKey()) . ".php";
        
        @unlink($fieldClass);
        
        
        // update classes
        $classList = new Object_Class_List();
        $classes = $classList->load();
        if(is_array($classes)){
            foreach($classes as $class){
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if($fieldDef instanceof Object_Class_Data_Fieldcollections) {
                        if(in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                            $this->getResource()->delete($class);
                            break;
                        }
                    }
                }
            }
        }
    }
}
