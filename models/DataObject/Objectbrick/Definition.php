<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Objectbrick;

use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\DataObject\Objectbrick\Definition\Dao getDao()
 * @method string getTableName(DataObject\ClassDefinition $class, $query)
 */
class Definition extends Model\DataObject\Fieldcollection\Definition
{
    use Model\DataObject\ClassDefinition\Helper\VarExport;
    use DataObject\Traits\LocateFileTrait;
    use DataObject\Traits\FieldcollectionObjectbrickDefinitionTrait;

    /**
     * @var array
     */
    public $classDefinitions = [];

    /**
     * @var array
     */
    private $oldClassDefinitions = [];

    /**
     * @param array $classDefinitions
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
     * @param string $key
     *
     * @return self|null
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
            $def = new Definition();
            $def->setKey($key);
            $fieldFile = $def->getDefinitionFile();

            if (is_file($fieldFile)) {
                $brick = include $fieldFile;
                \Pimcore\Cache\Runtime::set($cacheKey, $brick);
            }
        }

        if ($brick) {
            return $brick;
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    private function checkTablenames()
    {
        $tables = [];
        $key = $this->getKey();
        if (!$this->getFieldDefinitions()) {
            return;
        }
        $isLocalized = $this->getFieldDefinition('localizedfields') ? true : false;

        $classDefinitions = $this->getClassDefinitions();
        $validLanguages = Tool::getValidLanguages();
        foreach ($classDefinitions as $classDef) {
            $classname = $classDef['classname'];

            $class = DataObject\ClassDefinition::getByName($classname);

            if (!$class) {
                Logger::error('class ' . $classname . " doesn't exist anymore");
                continue;
            }

            $tables[] = 'object_brick_query_' . $key .  '_' . $class->getId();
            $tables[] = 'object_brick_store_' . $key .  '_' . $class->getId();
            if ($isLocalized) {
                foreach ($validLanguages as $validLanguage) {
                    $tables[] = 'object_brick_localized_query_' . $key . '_' . $class->getId() . '_' . $validLanguage;
                    $tables[] = 'object_brick_localized_' . $key . '_' . $class->getId() . '_' . $validLanguage;
                }
            }
        }

        array_multisort(array_map('strlen', $tables), $tables);
        $longestTablename = end($tables);

        $length = strlen($longestTablename);
        if ($length > 64) {
            throw new \Exception('table name ' . $longestTablename . ' would be too long. Max length is 64. Current length would be ' .  $length . '.');
        }
    }

    /**
     * @param bool $saveDefinitionFile
     *
     * @throws \Exception
     */
    public function save($saveDefinitionFile = true)
    {
        if (!$this->getKey()) {
            throw new \Exception('A object-brick needs a key to be saved!');
        }

        if (!preg_match('/[a-zA-Z]+[a-zA-Z0-9]+/', $this->getKey())) {
            throw new \Exception(sprintf('Invalid key for object-brick: %s', $this->getKey()));
        }

        if ($this->getParentClass() && !preg_match('/^[a-zA-Z_\x7f-\xff\\\][a-zA-Z0-9_\x7f-\xff\\\]*$/', $this->getParentClass())) {
            throw new \Exception(sprintf('Invalid parentClass value for class definition: %s',
                $this->getParentClass()));
        }

        $this->checkTablenames();
        $this->checkContainerRestrictions();

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

        $this->generateClassFiles($saveDefinitionFile);

        $cacheKey = 'objectbrick_' . $this->getKey();
        // for localized fields getting a fresh copy
        Runtime::set($cacheKey, $this);

        $this->createContainerClasses();
        $this->updateDatabase();
    }

    private function enforceBlockRules($fds, $found = [])
    {
        if (($found['block'] ?? false) && ($found['localizedfield'] ?? false)) {
            throw new \Exception('A localizedfield cannot be nested inside a block and vice versa');
        }
        /** @var DataObject\ClassDefinition\Data $fd */
        foreach ($fds as $fd) {
            $childParams = $found;
            if ($fd instanceof DataObject\ClassDefinition\Data\Block) {
                $childParams['block'] = true;
            } elseif ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $childParams['localizedfield'] = true;
            }
            if (method_exists($fd, 'getFieldDefinitions')) {
                $this->enforceBlockRules($fd->getFieldDefinitions(), $childParams);
            }
        }
    }

    private function checkContainerRestrictions()
    {
        $fds = $this->getFieldDefinitions();
        $this->enforceBlockRules($fds);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateClassFiles($generateDefinitionFile = true)
    {
        $existingDefinition = Definition::getByKey($this->getKey());
        $isUpdate = $existingDefinition != null;

        if ($isUpdate && !$this->isWritable()) {
            throw new \Exception('brick updates in config folder not allowed');
        }

        $definitionFile = $this->getDefinitionFile();

        $infoDocBlock = $this->getInfoDocBlock();

        if ($generateDefinitionFile) {
            $this->cleanupOldFiles($definitionFile);

            /** @var self $clone */
            $clone = DataObject\Service::cloneDefinition($this);
            $clone->setDao(null);
            unset($clone->oldClassDefinitions);
            unset($clone->fieldDefinitions);

            DataObject\ClassDefinition::cleanupForExport($clone->layoutDefinitions);

            $exportedClass = var_export($clone, true);

            $data = '<?php ';
            $data .= "\n\n";
            $data .= $infoDocBlock;
            $data .= "\n\n";

            $data .= "\nreturn " . $exportedClass . ";\n";

            \Pimcore\File::put($definitionFile, $data);
        }

        $extendClass = 'DataObject\\Objectbrick\\Data\\AbstractData';
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
            $extendClass = '\\' . ltrim($extendClass, '\\');
        }

        // create class

        $cd = '<?php ';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject\\Objectbrick\\Data;';
        $cd .= "\n\n";

        $useParts = [
            'Pimcore\Model\DataObject',
            'Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException',
            'Pimcore\Model\DataObject\PreGetValueHookInterface',
        ];

        $cd .= DataObject\ClassDefinition\Service::buildUseCode($useParts);

        $cd .= "\n";

        $implementsParts = [];
        $implements = DataObject\ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $this->getImplementsInterfaces());

        $cd .= 'class ' . ucfirst($this->getKey()) . ' extends ' . $extendClass . $implements .' {';
        $cd .= "\n\n";

        $cd .= 'protected $type = "' . $this->getKey() . "\";\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                $cd .= 'protected $' . $key . ";\n";
            }
        }

        $cd .= "\n\n";

        $cd .= '/**' ."\n";
        $cd .= '* ' . ucfirst($this->getKey()) . ' constructor.' . "\n";
        $cd .= '* @param DataObject\Concrete $object' . "\n";
        $cd .= '*/' . "\n";

        $cd .= 'public function __construct(DataObject\Concrete $object) {' . "\n";
        $cd .= "\t" . 'parent::__construct($object);' . "\n";
        $cd .= "\t" .'$this->markFieldDirty("_self");' . "\n";
        $cd .= '}' . "\n";

        $cd .= "\n\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                $cd .= $def->getGetterCodeObjectbrick($this);

                if ($def instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getGetterCode($this);
                }

                $cd .= $def->getSetterCodeObjectbrick($this);

                if ($def instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getSetterCode($this);
                }
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        File::putPhpFile($this->getPhpClassFile(), $cd);
    }

    /**
     * @param array $definitions
     *
     * @return array
     */
    private function buildClassList($definitions)
    {
        $result = [];
        foreach ($definitions as $definition) {
            $result[] = $definition['classname'] . '-' . $definition['fieldname'];
        }

        return $result;
    }

    /**
     * Returns a list of classes which need to be "rebuild" because they are affected of changes.
     *
     * @param self $oldObject
     *
     * @return array
     */
    private function getClassesToCleanup($oldObject)
    {
        $oldDefinitions = $oldObject->getClassDefinitions() ? $oldObject->getClassDefinitions() : [];
        $newDefinitions = $this->getClassDefinitions() ? $this->getClassDefinitions() : [];

        $old = $this->buildClassList($oldDefinitions);
        $new = $this->buildClassList($newDefinitions);

        $diff1 = array_diff($old, $new);
        $diff2 = array_diff($new, $old);

        $diff = array_merge($diff1, $diff2);
        $result = [];
        foreach ($diff as $item) {
            $parts = explode('-', $item);
            $result[] = ['classname' => $parts[0], 'fieldname' => $parts[1]];
        }

        return $result;
    }

    /**
     * @param string $serializedFilename
     */
    private function cleanupOldFiles($serializedFilename)
    {
        $oldObject = null;
        $this->oldClassDefinitions = [];
        if (file_exists($serializedFilename)) {
            $oldObject = include $serializedFilename;
        }

        if ($oldObject && !empty($oldObject->classDefinitions)) {
            $classlist = $this->getClassesToCleanup($oldObject);

            foreach ($classlist as $cl) {
                $this->oldClassDefinitions[$cl['classname']] = $cl['classname'];
                $class = DataObject\ClassDefinition::getByName($cl['classname']);
                if ($class) {
                    $path = $this->getContainerClassFolder($class->getName());
                    @unlink($path . '/' . ucfirst($cl['fieldname'] . '.php'));

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
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

                if (empty($processedClasses[$cl['classname']])) {
                    $class = DataObject\ClassDefinition::getByName($cl['classname']);
                    $this->getDao()->createUpdateTable($class);
                    $processedClasses[$cl['classname']] = true;
                }
            }
        }

        if (!empty($this->oldClassDefinitions)) {
            foreach ($this->oldClassDefinitions as $cl) {
                $class = DataObject\ClassDefinition::getByName($cl);
                if ($class) {
                    $this->getDao()->delete($class);

                    foreach ($class->getFieldDefinitions() as $fieldDef) {
                        if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
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
     * @param DataObject\ClassDefinition $class
     *
     * @return array
     */
    private function getAllowedTypesWithFieldname(DataObject\ClassDefinition $class)
    {
        $result = [];
        $fieldDefinitions = $class->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if (!$fd instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                continue;
            }

            $allowedTypes = $fd->getAllowedTypes() ? $fd->getAllowedTypes() : [];
            foreach ($allowedTypes as $allowedType) {
                $result[] = $fd->getName() . '-' . $allowedType;
            }
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    private function createContainerClasses()
    {
        $containerDefinition = [];

        if (!empty($this->classDefinitions)) {
            foreach ($this->classDefinitions as $cl) {
                $containerDefinition[$cl['classname']][$cl['fieldname']][] = $this->key;

                $class = DataObject\ClassDefinition::getByName($cl['classname']);
                if (!$class) {
                    throw new \Exception('Could not load class ' . $cl['classname']);
                }

                $fd = $class->getFieldDefinition($cl['fieldname']);
                if (!$fd instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                    throw new \Exception('Could not resolve field definition for ' . $cl['fieldname']);
                }

                $old = $this->getAllowedTypesWithFieldname($class);

                $allowedTypes = $fd->getAllowedTypes() ? $fd->getAllowedTypes() : [];

                if (!in_array($this->key, $allowedTypes)) {
                    $allowedTypes[] = $this->key;
                }

                $fd->setAllowedTypes($allowedTypes);
                $new = $this->getAllowedTypesWithFieldname($class);

                if (array_diff($new, $old) || array_diff($old, $new)) {
                    $class->save();
                } else {
                    // still, the brick fields definitions could have changed.
                    Cache::clearTag('class_'.$class->getId());
                    Logger::debug('Objectbrick ' . $this->getKey() . ', no change for class ' . $class->getName());
                }
            }
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
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
            $class = DataObject\ClassDefinition::getByName($classId);

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
                $cd .= 'use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;';
                $cd .= "\n\n";
                $cd .= 'class ' . $className . ' extends \\Pimcore\\Model\\DataObject\\Objectbrick {';
                $cd .= "\n\n";

                $cd .= 'protected $brickGetters = [' . "'" . implode("','", $brickKeys) . "'];\n";
                $cd .= "\n\n";

                foreach ($brickKeys as $brickKey) {
                    $cd .= 'protected $' . $brickKey . " = null;\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @return \\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickKey) . "|null\n";
                    $cd .= '*/' . "\n";
                    $cd .= 'public function get' . ucfirst($brickKey) . "() { \n";

                    if ($class->getAllowInherit()) {
                        $cd .= "\t" . 'if(!$this->' . $brickKey . ' && \\Pimcore\\Model\\DataObject::doGetInheritedValues($this->getObject())) { ' . "\n";
                        $cd .= "\t\t" . 'try {' . "\n";
                        $cd .= "\t\t\t" . '$brickContainer = $this->getObject()->getValueFromParent("' . $fieldname . '");' . "\n";
                        $cd .= "\t\t\t" . 'if(!empty($brickContainer)) {' . "\n";
                        $cd .= "\t\t\t\t" . '//check if parent object has brick, and if so, create an empty brick to enable inheritance' . "\n";
                        $cd .= "\t\t\t\t" . '$parentBrick = $this->getObject()->getValueFromParent("' . $fieldname . '")->get' . ucfirst($brickKey) . "(); \n";
                        $cd .= "\t\t\t\t" . 'if (!empty($parentBrick)) {' . "\n";
                        $cd .= "\t\t\t\t\t" . '$brickType = "\\\Pimcore\\\Model\\\DataObject\\\Objectbrick\\\Data\\\" . ucfirst($parentBrick->getType());' . "\n";
                        $cd .= "\t\t\t\t\t" . '$brick = new $brickType($this->getObject());' . "\n";
                        $cd .= "\t\t\t\t\t" . '$brick->setFieldname("' . $fieldname . '");' . "\n";
                        $cd .= "\t\t\t\t\t" . '$this->set'. ucfirst($brickKey) . '($brick);' . "\n";
                        $cd .= "\t\t\t\t\t" . 'return $brick;' . "\n";
                        $cd .= "\t\t\t\t" . '}' . "\n";
                        $cd .= "\t\t\t" . "}\n";
                        $cd .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
                        $cd .= "\t\t\t" . '// no data from parent available, continue ... ' . "\n";
                        $cd .= "\t\t" . '}' . "\n";
                        $cd .= "\t" . "}\n";
                    }
                    $cd .= '   return $this->' . $brickKey . "; \n";

                    $cd .= "}\n\n";

                    $cd .= '/**' . "\n";
                    $cd .= '* @param \\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickKey) . ' $' . $brickKey . "\n";
                    $cd .= '* @return \\'.$namespace.'\\'.$className."\n";
                    $cd .= '*/' . "\n";
                    $cd .= 'public function set' . ucfirst($brickKey) . ' (' . '$' . $brickKey . ") {\n";
                    $cd .= "\t" . '$this->' . $brickKey . ' = ' . '$' . $brickKey . ";\n";
                    $cd .= "\t" . 'return $this' . ";\n";
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
     * @param string $classname
     * @param string $fieldname
     *
     * @return string
     */
    private function getContainerClassName($classname, $fieldname)
    {
        return ucfirst($fieldname);
    }

    /**
     * @param string $classname
     * @param string $fieldname
     *
     * @return string
     */
    private function getContainerNamespace($classname, $fieldname)
    {
        return 'Pimcore\\Model\\DataObject\\' . ucfirst($classname);
    }

    /**
     * @param string $classname
     *
     * @return string
     */
    private function getContainerClassFolder($classname)
    {
        return PIMCORE_CLASS_DIRECTORY . '/DataObject/' . ucfirst($classname);
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

                if (!isset($processedClasses[$cl['classname']])) {
                    $processedClasses[$cl['classname']] = true;
                    $class = DataObject\ClassDefinition::getByName($cl['classname']);
                    if ($class instanceof DataObject\ClassDefinition) {
                        $this->getDao()->delete($class);

                        foreach ($class->getFieldDefinitions() as $fieldDef) {
                            if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
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

        // update classes
        $classList = new DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        if (is_array($classes)) {
            foreach ($classes as $class) {
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if ($fieldDef instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                        if (in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if (method_exists($fieldDefinition, 'enrichFieldDefinition')) {
            $context['containerType'] = 'objectbrick';
            $context['containerKey'] = $this->getKey();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @internal
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        if (getenv('PIMCORE_CLASS_DEFINITION_WRITABLE')) {
            return true;
        }

        return !str_starts_with($this->getDefinitionFile(), PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY);
    }

    /**
     * @internal
     *
     * @param string|null $key
     *
     * @return string
     */
    public function getDefinitionFile($key = null)
    {
        return $this->locateFile($key ?? $this->getKey(), 'objectbricks/%s.php');
    }

    /**
     * @internal
     *
     * @return string
     */
    protected function getPhpClassFile()
    {
        return $this->locateFile(ucfirst($this->getKey()), 'DataObject/Objectbrick/Data/%s.php');
    }
}
