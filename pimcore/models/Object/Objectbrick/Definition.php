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
 * @package    Object\Objectbrick
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Objectbrick;

use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @method \Pimcore\Model\Object\Objectbrick\Definition\Dao getDao()
 */
class Definition extends Model\Object\Fieldcollection\Definition
{
    use Model\Object\ClassDefinition\Helper\VarExport;

    /**
     * @var array
     */
    public $classDefinitions = [];

    /**
     * @var array
     */
    private $oldClassDefinitions = [];

    /**
     * @param $classDefinitions
     *
     * @return $this
     */
    public function setClassDefinitions($classDefinitions)
    {
        $this->classDefinitions = $classDefinitions;

        return $this;
    }

    /**
     * @return array
     */
    public function getClassDefinitions()
    {
        return $this->classDefinitions;
    }

    /**
     * @static
     *
     * @throws \Exception
     *
     * @param $key
     *
     * @return mixed
     */
    public static function getByKey($key)
    {
        $brick = null;
        $cacheKey = 'objectbrick_' . $key;

        try {
            $brick = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$brick) {
                throw new \Exception('ObjectBrick in Registry is not valid');
            }
        } catch (\Exception $e) {
            $objectBrickFolder = PIMCORE_CLASS_DIRECTORY . '/objectbricks';
            $fieldFile = $objectBrickFolder . '/' . $key . '.php';

            if (is_file($fieldFile)) {
                $brick = include $fieldFile;
                \Pimcore\Cache\Runtime::set($cacheKey, $brick);
            }
        }

        if ($brick) {
            return $brick;
        }

        throw new \Exception('Object-Brick with key: ' . $key . ' does not exist.');
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->getKey()) {
            throw new \Exception('A object-brick needs a key to be saved!');
        }

        $definitionFile = $this->getDefinitionFile();

        $newClassDefinitions = [];
        $classDefinitionsToDelete = [];

        foreach ($this->classDefinitions as $cl) {
            if (!isset($cl['deleted']) || !$cl['deleted']) {
                $newClassDefinitions[] = $cl;
            } else {
                $classDefinitionsToDelete[] = $cl;
            }
        }

        $this->classDefinitions = $newClassDefinitions;

        $infoDocBlock = $this->getInfoDocBlock();

        $this->cleanupOldFiles($definitionFile);

        $clone = clone $this;
        $clone->setDao(null);
        unset($clone->oldClassDefinitions);
        unset($clone->fieldDefinitions);

        $exportedClass = var_export($clone, true);

        $data = '<?php ';
        $data .= "\n\n";
        $data .= $infoDocBlock;
        $data .= "\n\n";

        $data .= "\nreturn " . $exportedClass . ";\n";

        \Pimcore\File::put($definitionFile, $data);

        $extendClass = 'Object\\Objectbrick\\Data\\AbstractData';
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
            $extendClass = '\\' . ltrim($extendClass, '\\');
        }

        // create class

        $cd = '<?php ';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\Object\\Objectbrick\\Data;';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\\Model\\Object;';
        $cd .= "\n\n";

        $cd .= 'class ' . ucfirst($this->getKey()) . ' extends ' . $extendClass . '  {';
        $cd .= "\n\n";

        $cd .= 'public $type = "' . $this->getKey() . "\";\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                $cd .= 'public $' . $key . ";\n";
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

        File::put($this->getPhpClassFile(), $cd);

        $this->createContainerClasses();
        $this->updateDatabase();
    }

    /**
     * @param $serializedFilename
     */
    private function cleanupOldFiles($serializedFilename)
    {
        $oldObject = null;
        $this->oldClassDefinitions = [];
        if (file_exists($serializedFilename)) {
            $oldObject = include $serializedFilename;
        }

        if ($oldObject && !empty($oldObject->classDefinitions)) {
            foreach ($oldObject->classDefinitions as $cl) {
                $this->oldClassDefinitions[$cl['classname']] = $cl['classname'];
                $class = Object\ClassDefinition::getById($cl['classname']);
                if ($class) {
                    $path = $this->getContainerClassFolder($class->getName());
                    @unlink($path . '/' . ucfirst($cl['fieldname'] . '.php'));

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if ($idx !== false) {
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
     * Update Database according to class-definition
     */
    private function updateDatabase()
    {
        $processedClasses = [];
        if (!empty($this->classDefinitions)) {
            foreach ($this->classDefinitions as $cl) {
                unset($this->oldClassDefinitions[$cl['classname']]);

                if (!isset($processedClasses[$cl['classname']]) || !$processedClasses[$cl['classname']]) {
                    $class = Object\ClassDefinition::getById($cl['classname']);
                    $this->getDao()->createUpdateTable($class);
                    $processedClasses[$cl['classname']] = true;
                }
            }
        }

        if (!empty($this->oldClassDefinitions)) {
            foreach ($this->oldClassDefinitions as $cl) {
                $class = Object\ClassDefinition::getById($cl);
                if ($class) {
                    $this->getDao()->delete($class);

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if ($idx !== false) {
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
     *
     * @todo: creates a PHP-Dock with "@return void" (line 351)
     */
    private function createContainerClasses()
    {
        $containerDefinition = [];

        if (!empty($this->classDefinitions)) {
            foreach ($this->classDefinitions as $cl) {
                $containerDefinition[$cl['classname']][$cl['fieldname']][] = $this->key;

                $class = Object\ClassDefinition::getById($cl['classname']);

                $fd = $class->getFieldDefinition($cl['fieldname']);
                if (!$fd) {
                    throw new \Exception('Could not resolve field definition for ' . $cl['fieldname']);
                }
                $allowedTypes = $fd->getAllowedTypes();
                if (!in_array($this->key, $allowedTypes)) {
                    $allowedTypes[] = $this->key;
                }
                $fd->setAllowedTypes($allowedTypes);
                $class->save();
            }
        }

        $list = new Object\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $def) {
            if ($this->key != $def->getKey()) {
                $classDefinitions = $def->getClassDefinitions();
                if (!empty($classDefinitions)) {
                    foreach ($classDefinitions as $cl) {
                        $containerDefinition[$cl['classname']][$cl['fieldname']][] = $def->getKey();
                    }
                }
            }
        }

        foreach ($containerDefinition as $classId => $cd) {
            $class = Object\ClassDefinition::getById($classId);

            if (!$class) {
                continue;
            }

            foreach ($cd as $fieldname => $brickKeys) {
                $className = $this->getContainerClassName($class->getName(), $fieldname);
                $namespace = $this->getContainerNamespace($class->getName(), $fieldname);

                $cd = '<?php ';

                $cd .= "\n\n";
                $cd .= 'namespace ' . $namespace . ';';
                $cd .= "\n\n";
                $cd .= 'class ' . $className . ' extends \\Pimcore\\Model\\Object\\Objectbrick {';
                $cd .= "\n\n";

                $cd .= "\n\n";
                $cd .= 'protected $brickGetters = array(' . "'" . implode("','", $brickKeys) . "');\n";
                $cd .= "\n\n";

                foreach ($brickKeys as $brickKey) {
                    $cd .= 'public $' . $brickKey . " = null;\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @return \\Pimcore\\Model\\Object\\Objectbrick\\Data\\' . $brickKey . "\n";
                    $cd .= '*/' . "\n";
                    $cd .= 'public function get' . ucfirst($brickKey) . "() { \n";

                    if ($class->getAllowInherit()) {
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
                    $cd .= 'public function set' . ucfirst($brickKey) . ' (' . '$' . $brickKey . ") {\n";
                    $cd .= "\t" . '$this->' . $brickKey . ' = ' . '$' . $brickKey . ";\n";
                    $cd .= "\t" . 'return $this;' . ";\n";
                    $cd .= "}\n\n";
                }

                $cd .= "}\n";
                $cd .= "\n";

                $folder = $this->getContainerClassFolder($class->getName());
                if (!is_dir($folder)) {
                    File::mkdir($folder);
                }

                $file = $folder . '/' . ucfirst($fieldname) . '.php';
                File::put($file, $cd);
            }
        }
    }

    /**
     * @param $classname
     * @param $fieldname
     *
     * @return string
     */
    private function getContainerClassName($classname, $fieldname)
    {
        return ucfirst($fieldname);
    }

    /**
     * @param $classname
     * @param $fieldname
     *
     * @return string
     */
    private function getContainerNamespace($classname, $fieldname)
    {
        return 'Pimcore\\Model\\Object\\' . ucfirst($classname);
    }

    /**
     * @param $classname
     *
     * @return string
     */
    private function getContainerClassFolder($classname)
    {
        return PIMCORE_CLASS_DIRECTORY . '/Object/' . ucfirst($classname);
    }

    /**
     * Delete Brick Definition
     */
    public function delete()
    {
        @unlink($this->getDefinitionFile());
        @unlink($this->getPhpClassFile());

        $processedClasses = [];
        if (!empty($this->classDefinitions)) {
            foreach ($this->classDefinitions as $cl) {
                unset($this->oldClassDefinitions[$cl['classname']]);

                if (!$processedClasses[$cl['classname']]) {
                    $class = Object\ClassDefinition::getById($cl['classname']);
                    $this->getDao()->delete($class);
                    $processedClasses[$cl['classname']] = true;

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                            $allowedTypes = $fieldDef->getAllowedTypes();
                            $idx = array_search($this->getKey(), $allowedTypes);
                            if ($idx !== false) {
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
        if (is_array($classes)) {
            foreach ($classes as $class) {
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if ($fieldDef instanceof Object\ClassDefinition\Data\Objectbricks) {
                        if (in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getDefinitionFile()
    {
        $objectBrickFolder = PIMCORE_CLASS_DIRECTORY . '/objectbricks';
        $definitionFile = $objectBrickFolder . '/' . $this->getKey() . '.php';

        return $definitionFile;
    }

    /**
     * @return string
     */
    protected function getPhpClassFile()
    {
        $classFolder = PIMCORE_CLASS_DIRECTORY . '/Object/Objectbrick/Data';
        $classFile = $classFolder . '/' . ucfirst($this->getKey()) . '.php';

        return $classFile;
    }
}
