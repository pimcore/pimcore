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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Objectbrick;

use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\DataObject\Objectbrick\Definition\Dao getDao()
 * @method string getTableName(DataObject\ClassDefinition $class, $query)
 * @method void createUpdateTable(DataObject\ClassDefinition $class)
 * @method string getLocalizedTableName(DataObject\ClassDefinition $class, $query)
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
                    $tables[] = 'object_brick_localized_' . $key . '_' . $class->getId();
                }
            }
        }

        $tablesLen = array_map('strlen', $tables);
        array_multisort($tablesLen, $tables);
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

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd->isForbiddenName()) {
                throw new \Exception(sprintf('Forbidden name used for field definition: %s', $fd->getName()));
            }

            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->preSave($this);
            }
        }

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

        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->postSave($this);
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $fds
     * @param array $found
     *
     * @throws \Exception
     */
    private function enforceBlockRules($fds, $found = [])
    {
        foreach ($fds as $fd) {
            $childParams = $found;
            if ($fd instanceof DataObject\ClassDefinition\Data\Block) {
                $childParams['block'] = true;
            } elseif ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                if ($found['block'] ?? false) {
                    throw new \Exception('A localizedfield cannot be nested inside a block');
                }
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
        if ($generateDefinitionFile && !$this->isWritable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $definitionFile = $this->getDefinitionFile();

        if ($generateDefinitionFile) {
            $this->cleanupOldFiles($definitionFile);

            /** @var self $clone */
            $clone = DataObject\Service::cloneDefinition($this);
            $clone->setDao(null);
            unset($clone->oldClassDefinitions);
            unset($clone->fieldDefinitions);

            DataObject\ClassDefinition::cleanupForExport($clone->layoutDefinitions);

            $exportedClass = var_export($clone, true);

            $data = '<?php';
            $data .= "\n\n";
            $data .= $this->getInfoDocBlock();
            $data .= "\n\n";

            $data .= 'return ' . $exportedClass . ";\n";

            \Pimcore\File::put($definitionFile, $data);
        }

        \Pimcore::getContainer()->get(PHPObjectBrickClassDumperInterface::class)->dumpPHPClasses($this);
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
     * @internal
     *
     * @return array
     */
    public function getAllowedTypesWithFieldname(DataObject\ClassDefinition $class)
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
                $class = DataObject\ClassDefinition::getByName($cl['classname']);
                if (!$class) {
                    throw new \Exception('Could not load class ' . $cl['classname']);
                }

                $fd = $class->getFieldDefinition($cl['fieldname']);
                if (!$fd instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                    throw new \Exception('Could not resolve field definition for ' . $cl['fieldname']);
                }

                $old = $this->getAllowedTypesWithFieldname($class);

                $allowedTypes = $fd->getAllowedTypes() ?: [];

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

        \Pimcore::getContainer()->get(PHPObjectBrickContainerClassDumperInterface::class)->dumpContainerClasses($this);
    }

    /**
     * @param string $classname
     * @param string $fieldname
     *
     * @internal
     *
     * @return string
     */
    public function getContainerClassName($classname, $fieldname)
    {
        return ucfirst($fieldname);
    }

    /**
     * @param string $classname
     * @param string $fieldname
     *
     * @internal
     *
     * @return string
     */
    public function getContainerNamespace($classname, $fieldname)
    {
        return 'Pimcore\\Model\\DataObject\\' . ucfirst($classname);
    }

    /**
     * @param string $classname
     *
     * @internal
     *
     * @return string
     */
    public function getContainerClassFolder($classname)
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
        //TODO Pimcore 11: remove method_exists BC layer
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface || method_exists($fieldDefinition, 'enrichFieldDefinition')) {
            if (!$fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
                trigger_deprecation('pimcore/pimcore', '10.1',
                    sprintf('Usage of method_exists is deprecated since version 10.1 and will be removed in Pimcore 11.' .
                    'Implement the %s interface instead.', FieldDefinitionEnrichmentInterface::class));
            }
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
        return $_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE'] ?? !str_starts_with($this->getDefinitionFile(), PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY);
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
        return $this->locateDefinitionFile($key ?? $this->getKey(), 'objectbricks/%s.php');
    }

    /**
     * @internal
     *
     * @return string
     */
    public function getPhpClassFile()
    {
        return $this->locateFile(ucfirst($this->getKey()), 'DataObject/Objectbrick/Data/%s.php');
    }
}
