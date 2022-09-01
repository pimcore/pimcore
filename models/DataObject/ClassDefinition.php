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

namespace Pimcore\Model\DataObject;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\DataObject\ClassBuilder\FieldDefinitionDocBlockBuilderInterface;
use Pimcore\DataObject\ClassBuilder\PHPClassDumperInterface;
use Pimcore\Db;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Dao getDao()
 */
final class ClassDefinition extends Model\AbstractModel
{
    use DataObject\ClassDefinition\Helper\VarExport;
    use DataObject\Traits\LocateFileTrait;
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @internal
     *
     * @var string|null
     */
    public $id;

    /**
     * @internal
     *
     * @var string|null
     */
    public $name;

    /**
     * @internal
     *
     * @var string
     */
    public $description = '';

    /**
     * @internal
     *
     * @var int|null
     */
    public $creationDate;

    /**
     * @internal
     *
     * @var int|null
     */
    public $modificationDate;

    /**
     * @internal
     *
     * @var int|null
     */
    public $userOwner;

    /**
     * @internal
     *
     * @var int|null
     */
    public $userModification;

    /**
     * @internal
     *
     * @var string
     */
    public $parentClass = '';

    /**
     * Comma separated list of interfaces
     *
     * @internal
     *
     * @var string|null
     */
    public $implementsInterfaces;

    /**
     * Name of the listing parent class if set
     *
     * @internal
     *
     * @var string
     */
    public $listingParentClass = '';

    /**
     * @internal
     *
     * @var string
     */
    public $useTraits = '';

    /**
     * @internal
     *
     * @var string
     */
    public $listingUseTraits = '';

    /**
     * @internal
     *
     * @var bool
     */
    protected $encryption = false;

    /**
     * @internal
     *
     * @var array
     */
    protected $encryptedTables = [];

    /**
     * @internal
     *
     * @var bool
     */
    public $allowInherit = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $allowVariants = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $showVariants = false;

    /**
     * @internal
     *
     * @var DataObject\ClassDefinition\Data[]
     */
    public array $fieldDefinitions = [];

    /**
     * @internal
     *
     * @var DataObject\ClassDefinition\Layout|null
     */
    public $layoutDefinitions;

    /**
     * @internal
     *
     * @var string|null
     */
    public $icon;

    /**
     * @internal
     *
     * @var string|null
     */
    public $previewUrl;

    /**
     * @internal
     *
     * @var string|null
     */
    public $group;

    /**
     * @internal
     *
     * @var bool
     */
    public $showAppLoggerTab = false;

    /**
     * @internal
     *
     * @var string
     */
    public $linkGeneratorReference;

    /**
     * @internal
     *
     * @var string|null
     */
    public $previewGeneratorReference;

    /**
     * @internal
     *
     * @var array
     */
    public $compositeIndices = [];

    /**
     * @internal
     *
     * @var bool
     */
    public $showFieldLookup = false;

    /**
     * @internal
     *
     * @var array
     */
    public $propertyVisibility = [
        'grid' => [
            'id' => true,
            'path' => true,
            'published' => true,
            'modificationDate' => true,
            'creationDate' => true,
        ],
        'search' => [
            'id' => true,
            'path' => true,
            'published' => true,
            'modificationDate' => true,
            'creationDate' => true,
        ],
    ];

    /**
     * @internal
     *
     * @var bool
     */
    public $enableGridLocking = false;

    /**
     * @internal
     *
     * @var ClassDefinition\Data[]
     */
    private array $deletedDataComponents = [];

    /**
     * @param string $id
     * @param bool $force
     *
     * @return null|ClassDefinition
     *
     * @throws \Exception
     */
    public static function getById(string $id, $force = false)
    {
        $cacheKey = 'class_' . $id;

        try {
            if ($force) {
                throw new \Exception('Forced load');
            }
            $class = RuntimeCache::get($cacheKey);
            if (!$class) {
                throw new \Exception('Class in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $class = new self();
                $name = $class->getDao()->getNameById($id);
                $definitionFile = $class->getDefinitionFile($name);
                $class = @include $definitionFile;

                if (!$class instanceof self) {
                    throw new \Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

                $class->setId($id);

                RuntimeCache::set($cacheKey, $class);
            } catch (\Exception $e) {
                Logger::info($e->getMessage());

                return null;
            }
        }

        return $class;
    }

    /**
     * @param string $name
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name)
    {
        try {
            $class = new self();
            $id = $class->getDao()->getIdByName($name);

            return self::getById($id);
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param array $values
     *
     * @return self
     */
    public static function create($values = [])
    {
        $class = new self();
        $class->setValues($values);

        return $class;
    }

    /**
     * @internal
     *
     * @param string $name
     */
    public function rename($name)
    {
        $this->deletePhpClasses();
        $this->getDao()->updateClassNameInObjects($name);

        $this->setName($name);
        $this->save();
    }

    /**
     * @param mixed $data
     *
     * @internal
     */
    public static function cleanupForExport(&$data)
    {
        if (!is_object($data)) {
            return;
        }

        if ($data instanceof DataObject\ClassDefinition\Data\VarExporterInterface) {
            $blockedVars = $data->resolveBlockedVars();
            foreach ($blockedVars as $blockedVar) {
                if (isset($data->{$blockedVar})) {
                    unset($data->{$blockedVar});
                }
            }

            if (isset($data->blockedVarsForExport)) {
                unset($data->blockedVarsForExport);
            }
        }

        if (method_exists($data, 'getChildren')) {
            $children = $data->getChildren();
            if (is_array($children)) {
                foreach ($children as $child) {
                    self::cleanupForExport($child);
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function exists()
    {
        $name = $this->getDao()->getNameById($this->getId());

        return is_string($name);
    }

    /**
     * @param bool $saveDefinitionFile
     *
     * @throws \Exception
     * @throws DataObject\Exception\DefinitionWriteException
     */
    public function save($saveDefinitionFile = true)
    {
        if ($saveDefinitionFile && !$this->isWritable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd->isForbiddenName()) {
                throw new \Exception(sprintf('Forbidden name used for field definition: %s', $fd->getName()));
            }

            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->preSave($this);
            }
        }

        if (!$this->getId()) {
            $db = Db::get();
            $maxId = $db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM classes;');
            $maxId = $maxId ? $maxId + 1 : 1;
            $this->setId((string) $maxId);
        }

        if (!preg_match('/[a-zA-Z][a-zA-Z0-9_]+/', $this->getName())) {
            throw new \Exception(sprintf('Invalid name for class definition: %s', $this->getName()));
        }

        if (!preg_match('/[a-zA-Z0-9]([a-zA-Z0-9_]+)?/', $this->getId())) {
            throw new \Exception(sprintf('Invalid ID `%s` for class definition %s', $this->getId(), $this->getName()));
        }

        foreach (['parentClass', 'listingParentClass', 'useTraits', 'listingUseTraits'] as $propertyName) {
            $propertyValue = $this->{'get'.ucfirst($propertyName)}();
            if ($propertyValue && !preg_match('/^[a-zA-Z_\x7f-\xff\\\][a-zA-Z0-9_\x7f-\xff\\\ ,]*$/', $propertyValue)) {
                throw new \Exception(sprintf('Invalid %s value for class definition: %s', $propertyName,
                    $this->getParentClass()));
            }
        }

        $isUpdate = $this->exists();

        if (!$isUpdate) {
            $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_ADD);
        } else {
            $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_UPDATE);
        }

        $this->setModificationDate(time());

        $this->getDao()->save($isUpdate);

        $this->generateClassFiles($saveDefinitionFile);

        foreach ($fieldDefinitions as $fd) {
            // call the method "classSaved" if exists, this is used to create additional data tables or whatever which depends on the field definition, for example for localizedfields
            //TODO Pimcore 11 remove method_exists call
            if (!$fd instanceof ClassDefinition\Data\DataContainerAwareInterface && method_exists($fd, 'classSaved')) {
                $fd->classSaved($this);
            }
        }

        // empty object cache
        try {
            Cache::clearTag('class_'.$this->getId());
        } catch (\Exception $e) {
        }

        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->postSave($this);
            }
        }

        if ($isUpdate) {
            $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::POST_UPDATE);
        } else {
            $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::POST_ADD);
        }

        $this->deleteDeletedDataComponentsInCustomLayout();
    }

    /**
     * @param bool $generateDefinitionFile
     *
     * @throws \Exception
     *
     * @internal
     */
    public function generateClassFiles($generateDefinitionFile = true)
    {
        \Pimcore::getContainer()->get(PHPClassDumperInterface::class)->dumpPHPClasses($this);

        if ($generateDefinitionFile) {
            // save definition as a php file
            $definitionFile = $this->getDefinitionFile();
            if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
                throw new \Exception(
                    'Cannot write definition file in: '.$definitionFile.' please check write permission on this directory.'
                );
            }
            /** @var self $clone */
            $clone = DataObject\Service::cloneDefinition($this);
            $clone->setDao(null);
            $clone->fieldDefinitions = [];

            self::cleanupForExport($clone->layoutDefinitions);

            $exportedClass = var_export($clone, true);

            $data = '<?php';
            $data .= "\n\n";
            $data .= $this->getInfoDocBlock();
            $data .= "\n\n";

            $data .= 'return '.$exportedClass.";\n";

            \Pimcore\File::putPhpFile($definitionFile, $data);
        }
    }

    /**
     * @return string
     *
     * @internal
     */
    protected function getInfoDocBlock(): string
    {
        $cd = '/**' . "\n";
        $cd .= ' * Inheritance: '.($this->getAllowInherit() ? 'yes' : 'no')."\n";
        $cd .= ' * Variants: '.($this->getAllowVariants() ? 'yes' : 'no')."\n";

        if ($description = $this->getDescription()) {
            $description = str_replace(['/**', '*/', '//'], '', $description);
            $description = str_replace("\n", "\n * ", $description);

            $cd .= ' * '.$description."\n";
        }

        $cd .= " *\n";
        $cd .= " * Fields Summary:\n";

        $fieldDefinitionDocBlockBuilder = \Pimcore::getContainer()->get(FieldDefinitionDocBlockBuilderInterface::class);
        foreach ($this->getFieldDefinitions() as $fieldDefinition) {
            $cd .= ' * ' . str_replace("\n", "\n * ", trim($fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $cd .= ' */';

        return $cd;
    }

    public function delete()
    {
        $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_DELETE);

        // delete all objects using this class
        $list = new Listing();
        $list->setCondition('o_classId = ?', $this->getId());
        $list->load();

        foreach ($list->getObjects() as $o) {
            $o->delete();
        }

        $this->deletePhpClasses();

        // empty object cache
        try {
            Cache::clearTag('class_'.$this->getId());
        } catch (\Exception $e) {
        }

        // empty output cache
        try {
            Cache::clearTag('output');
        } catch (\Exception $e) {
        }

        $customLayouts = new ClassDefinition\CustomLayout\Listing();
        $id = $this->getId();
        $customLayouts->setFilter(function (DataObject\ClassDefinition\CustomLayout $layout) use ($id) {
            return $layout->getClassId() === $id;
        });
        $customLayouts = $customLayouts->load();

        foreach ($customLayouts as $customLayout) {
            $customLayout->delete();
        }

        $brickListing = new DataObject\Objectbrick\Definition\Listing();
        $brickListing = $brickListing->load();
        foreach ($brickListing as $brickDefinition) {
            $modified = false;

            $classDefinitions = $brickDefinition->getClassDefinitions();
            if (is_array($classDefinitions)) {
                foreach ($classDefinitions as $key => $classDefinition) {
                    if ($classDefinition['classname'] == $this->getId()) {
                        unset($classDefinitions[$key]);
                        $modified = true;
                    }
                }
            }
            if ($modified) {
                $brickDefinition->setClassDefinitions($classDefinitions);
                $brickDefinition->save();
            }
        }

        $this->getDao()->delete();

        $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::POST_DELETE);
    }

    private function deletePhpClasses()
    {
        // delete the class files
        @unlink($this->getPhpClassFile());
        @unlink($this->getPhpListingClassFile());
        @rmdir(dirname($this->getPhpListingClassFile()));
        @unlink($this->getDefinitionFile());
    }

    /**
     * @internal
     *
     * with PIMCORE_CLASS_DEFINITION_WRITABLE set, it globally allow/disallow creation and change in classes
     * when the ENV is not set, it allows modification and creation of new in classes in /var/classes but disables modification of classes in config/pimcore/classes
     * more details in 05_Deployment_Tools.md
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
     * @param string|null $name
     *
     * @return string
     */
    public function getDefinitionFile($name = null)
    {
        return $this->locateDefinitionFile($name ?? $this->getName(), 'definition_%s.php');
    }

    /**
     * @internal
     */
    public function getPhpClassFile(): string
    {
        return $this->locateFile(ucfirst($this->getName()), 'DataObject/%s.php');
    }

    /**
     * @internal
     */
    public function getPhpListingClassFile(): string
    {
        return $this->locateFile(ucfirst($this->getName()), 'DataObject/%s/Listing.php');
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @return int|null
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @return int|null
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int)$creationDate;

        return $this;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int)$modificationDate;

        return $this;
    }

    /**
     * @param int $userOwner
     *
     * @return $this
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = (int)$userOwner;

        return $this;
    }

    /**
     * @param int $userModification
     *
     * @return $this
     */
    public function setUserModification($userModification)
    {
        $this->userModification = (int)$userModification;

        return $this;
    }

    /**
     * @param array $context
     *
     * @return DataObject\ClassDefinition\Data[]
     */
    public function getFieldDefinitions($context = [])
    {
        if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
            return $this->fieldDefinitions;
        }

        $enrichedFieldDefinitions = [];
        foreach ($this->fieldDefinitions as $key => $fieldDefinition) {
            $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
            $enrichedFieldDefinitions[$key] = $fieldDefinition;
        }

        return $enrichedFieldDefinitions;
    }

    /**
     * @internal
     */
    protected function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['class'] = $this;
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @return DataObject\ClassDefinition\Layout|null
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions(array $fieldDefinitions)
    {
        $this->fieldDefinitions = $fieldDefinitions;

        return $this;
    }

    /**
     * @param string $key
     * @param DataObject\ClassDefinition\Data $data
     *
     * @return $this
     */
    public function addFieldDefinition($key, $data)
    {
        $this->fieldDefinitions[$key] = $data;

        return $this;
    }

    /**
     * @param string $key
     * @param array $context
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    public function getFieldDefinition($key, $context = [])
    {
        if (array_key_exists($key, $this->fieldDefinitions)) {
            if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
                return $this->fieldDefinitions[$key];
            }
            $fieldDefinition = $this->doEnrichFieldDefinition($this->fieldDefinitions[$key], $context);

            return $fieldDefinition;
        }

        return null;
    }

    /**
     * @param DataObject\ClassDefinition\Layout|null $layoutDefinitions
     *
     * @return $this
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $oldFieldDefinitions = null;
        if ($this->layoutDefinitions !== null) {
            $this->setDeletedDataComponents([]);
            $oldFieldDefinitions = $this->getFieldDefinitions();
        }

        $this->layoutDefinitions = $layoutDefinitions;

        $this->fieldDefinitions = [];
        $this->extractDataDefinitions($this->layoutDefinitions);

        if ($oldFieldDefinitions !== null) {
            $newFieldDefinitions = $this->getFieldDefinitions();
            $deletedComponents = [];
            foreach ($oldFieldDefinitions as $fieldDefinition) {
                if (!array_key_exists($fieldDefinition->getName(), $newFieldDefinitions)) {
                    array_push($deletedComponents, $fieldDefinition);
                }
            }
            $this->setDeletedDataComponents($deletedComponents);
        }

        return $this;
    }

    /**
     * @param DataObject\ClassDefinition\Layout|DataObject\ClassDefinition\Data $def
     */
    private function extractDataDefinitions($def)
    {
        if ($def instanceof DataObject\ClassDefinition\Layout) {
            if ($def->hasChildren()) {
                foreach ($def->getChildren() as $child) {
                    $this->extractDataDefinitions($child);
                }
            }
        }

        if ($def instanceof DataObject\ClassDefinition\Data) {
            $existing = $this->getFieldDefinition($def->getName());

            if (!$existing && method_exists($def, 'addReferencedField') && method_exists($def, 'setReferencedFields')) {
                $def->setReferencedFields([]);
            }

            if ($existing && method_exists($existing, 'addReferencedField')) {
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
     * @return string
     */
    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * @return string
     */
    public function getListingParentClass()
    {
        return $this->listingParentClass;
    }

    /**
     * @return string
     */
    public function getUseTraits()
    {
        return $this->useTraits;
    }

    /**
     * @param string $useTraits
     *
     * @return ClassDefinition
     */
    public function setUseTraits($useTraits)
    {
        $this->useTraits = (string) $useTraits;

        return $this;
    }

    /**
     * @return string
     */
    public function getListingUseTraits()
    {
        return $this->listingUseTraits;
    }

    /**
     * @param string $listingUseTraits
     *
     * @return ClassDefinition
     */
    public function setListingUseTraits($listingUseTraits)
    {
        $this->listingUseTraits = (string) $listingUseTraits;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowInherit()
    {
        return $this->allowInherit;
    }

    /**
     * @return bool
     */
    public function getAllowVariants()
    {
        return $this->allowVariants;
    }

    /**
     * @param string $parentClass
     *
     * @return $this
     */
    public function setParentClass($parentClass)
    {
        $this->parentClass = $parentClass;

        return $this;
    }

    /**
     * @param string $listingParentClass
     *
     * @return $this
     */
    public function setListingParentClass($listingParentClass)
    {
        $this->listingParentClass = (string) $listingParentClass;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEncryption(): bool
    {
        return $this->encryption;
    }

    /**
     * @param bool $encryption
     *
     * @return $this
     */
    public function setEncryption(bool $encryption)
    {
        $this->encryption = $encryption;

        return $this;
    }

    /**
     * @internal
     *
     * @param array $tables
     */
    public function addEncryptedTables(array $tables)
    {
        $this->encryptedTables = array_unique(array_merge($this->encryptedTables, $tables));
    }

    /**
     * @internal
     *
     * @param array $tables
     */
    public function removeEncryptedTables(array $tables)
    {
        foreach ($tables as $table) {
            if (($key = array_search($table, $this->encryptedTables)) !== false) {
                unset($this->encryptedTables[$key]);
            }
        }
    }

    /**
     * @internal
     *
     * @param string $table
     *
     * @return bool
     */
    public function isEncryptedTable(string $table): bool
    {
        return (array_search($table, $this->encryptedTables) === false) ? false : true;
    }

    /**
     * @return bool
     */
    public function hasEncryptedTables(): bool
    {
        return (bool) count($this->encryptedTables);
    }

    /**
     * @internal
     *
     * @param array $encryptedTables
     *
     * @return $this
     */
    public function setEncryptedTables(array $encryptedTables)
    {
        $this->encryptedTables = $encryptedTables;

        return $this;
    }

    /**
     * @param bool $allowInherit
     *
     * @return $this
     */
    public function setAllowInherit($allowInherit)
    {
        $this->allowInherit = (bool)$allowInherit;

        return $this;
    }

    /**
     * @param bool $allowVariants
     *
     * @return $this
     */
    public function setAllowVariants($allowVariants)
    {
        $this->allowVariants = (bool)$allowVariants;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     *
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return array
     */
    public function getPropertyVisibility()
    {
        return $this->propertyVisibility;
    }

    /**
     * @param array $propertyVisibility
     *
     * @return $this
     */
    public function setPropertyVisibility($propertyVisibility)
    {
        if (is_array($propertyVisibility)) {
            $this->propertyVisibility = $propertyVisibility;
        }

        return $this;
    }

    /**
     * @param string|null $previewUrl
     *
     * @return $this
     */
    public function setPreviewUrl($previewUrl)
    {
        $this->previewUrl = $previewUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreviewUrl()
    {
        return $this->previewUrl;
    }

    /**
     * @return string|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string|null $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @param string $description
     *
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
     * @param bool $showVariants
     *
     * @return $this
     */
    public function setShowVariants($showVariants)
    {
        $this->showVariants = (bool)$showVariants;

        return $this;
    }

    /**
     * @return bool
     */
    public function getShowVariants()
    {
        return $this->showVariants;
    }

    /**
     * @return bool
     */
    public function getShowAppLoggerTab()
    {
        return $this->showAppLoggerTab;
    }

    /**
     * @param bool $showAppLoggerTab
     *
     * @return $this
     */
    public function setShowAppLoggerTab($showAppLoggerTab)
    {
        $this->showAppLoggerTab = (bool) $showAppLoggerTab;

        return $this;
    }

    /**
     * @return bool
     */
    public function getShowFieldLookup()
    {
        return $this->showFieldLookup;
    }

    /**
     * @param bool $showFieldLookup
     *
     * @return $this
     */
    public function setShowFieldLookup($showFieldLookup)
    {
        $this->showFieldLookup = (bool) $showFieldLookup;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinkGeneratorReference()
    {
        return $this->linkGeneratorReference;
    }

    /**
     * @param string $linkGeneratorReference
     *
     * @return $this
     */
    public function setLinkGeneratorReference($linkGeneratorReference)
    {
        $this->linkGeneratorReference = $linkGeneratorReference;

        return $this;
    }

    /**
     * @return DataObject\ClassDefinition\LinkGeneratorInterface|null
     */
    public function getLinkGenerator()
    {
        return DataObject\ClassDefinition\Helper\LinkGeneratorResolver::resolveGenerator($this->getLinkGeneratorReference());
    }

    /**
     * @return string|null
     */
    public function getPreviewGeneratorReference(): ?string
    {
        return $this->previewGeneratorReference;
    }

    /**
     * @param string|null $previewGeneratorReference
     */
    public function setPreviewGeneratorReference(?string $previewGeneratorReference): void
    {
        $this->previewGeneratorReference = $previewGeneratorReference;
    }

    /**
     * @return DataObject\ClassDefinition\PreviewGeneratorInterface|null
     */
    public function getPreviewGenerator()
    {
        return DataObject\ClassDefinition\Helper\PreviewGeneratorResolver::resolveGenerator($this->getPreviewGeneratorReference());
    }

    /**
     * @return bool
     */
    public function isEnableGridLocking(): bool
    {
        return $this->enableGridLocking;
    }

    /**
     * @param bool $enableGridLocking
     */
    public function setEnableGridLocking(bool $enableGridLocking): void
    {
        $this->enableGridLocking = $enableGridLocking;
    }

    /**
     * @return string|null
     */
    public function getImplementsInterfaces(): ?string
    {
        return $this->implementsInterfaces;
    }

    /**
     * @param string|null $implementsInterfaces
     *
     * @return $this
     */
    public function setImplementsInterfaces(?string $implementsInterfaces)
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }

    /**
     * @return array
     */
    public function getCompositeIndices(): array
    {
        return $this->compositeIndices;
    }

    /**
     * @param array|null $compositeIndices
     *
     * @return $this
     */
    public function setCompositeIndices($compositeIndices)
    {
        $this->compositeIndices = $compositeIndices ?? [];

        return $this;
    }

    /**
     * @return ClassDefinition\Data[]
     */
    public function getDeletedDataComponents()
    {
        return $this->deletedDataComponents;
    }

    /**
     * @param ClassDefinition\Data[] $deletedDataComponents
     *
     * @return $this
     */
    public function setDeletedDataComponents(array $deletedDataComponents): ClassDefinition
    {
        $this->deletedDataComponents = $deletedDataComponents;

        return $this;
    }

    private function deleteDeletedDataComponentsInCustomLayout(): void
    {
        if (empty($this->getDeletedDataComponents())) {
            return;
        }
        $customLayouts = new ClassDefinition\CustomLayout\Listing();
        $id = $this->getId();
        $customLayouts->setFilter(function (DataObject\ClassDefinition\CustomLayout $layout) use ($id) {
            return $layout->getClassId() === $id;
        });
        $customLayouts = $customLayouts->load();

        foreach ($customLayouts as $customLayout) {
            $layoutDefinition = $customLayout->getLayoutDefinitions();
            $this->deleteDeletedDataComponentsInLayoutDefinition($layoutDefinition);
            $customLayout->setLayoutDefinitions($layoutDefinition);
            $customLayout->save();
        }
    }

    private function deleteDeletedDataComponentsInLayoutDefinition(ClassDefinition\Layout $layoutDefinition): void
    {
        $componentsToDelete = $this->getDeletedDataComponents();
        $componentDeleted = false;

        $children = &$layoutDefinition->getChildrenByRef();
        $count = count($children);
        for ($i = 0; $i < $count; $i++) {
            $component = $children[$i];
            if (in_array($component, $componentsToDelete)) {
                unset($children[$i]);
                $componentDeleted = true;
            }
            if ($component instanceof ClassDefinition\Layout) {
                $this->deleteDeletedDataComponentsInLayoutDefinition($component);
            }
        }
        if ($componentDeleted) {
            $children = array_values($children);
        }
    }

    public static function getByIdIgnoreCase(string $id): ClassDefinition|null
    {
        try {
            $class = new self();
            $name = $class->getDao()->getNameByIdIgnoreCase($id);
            $definitionFile = $class->getDefinitionFile($name);
            $class = @include $definitionFile;

            if (!$class instanceof self) {
                throw new \Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
            }

            $class->setId($id);
        } catch (\Exception $e) {
            Logger::info($e->getMessage());

            return null;
        }

        return $class;
    }
}
