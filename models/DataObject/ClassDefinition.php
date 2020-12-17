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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Cache;
use Pimcore\DataObject\ClassDefinition\DefinitionFileGeneratorInterface;
use Pimcore\DataObject\ClassDefinition\DefinitionPathResolverInterface;
use Pimcore\DataObject\ClassDefinition\DeleterInterface;
use Pimcore\DataObject\ClassDefinition\PHPFileGenerator;
use Pimcore\DataObject\ClassDefinition\PHPFileGeneratorInterface;
use Pimcore\Db;
use Pimcore\Event\DataObjectClassDefinitionEvents;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Dao getDao()
 */
class ClassDefinition extends Model\AbstractModel
{
    use DataObject\ClassDefinition\Helper\VarExport;

    /**
     * @var string
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
     * Comma separated list of interfaces
     *
     * @var string|null
     */
    public $implementsInterfaces;

    /**
     * Name of the listing parent class if set
     *
     * @var string
     */
    public $listingParentClass = '';

    /**
     * @var string
     */
    public $useTraits = '';

    /**
     * @var string
     */
    public $listingUseTraits = '';

    /**
     * @var bool
     */
    protected $encryption = false;

    /**
     * @var array
     */
    protected $encryptedTables = [];

    /**
     * @var bool
     */
    public $allowInherit = false;

    /**
     * @var bool
     */
    public $allowVariants = false;

    /**
     * @var bool
     */
    public $showVariants = false;

    /**
     * @var DataObject\ClassDefinition\Data[]
     */
    public $fieldDefinitions = [];

    /**
     * @var DataObject\ClassDefinition\Layout|null
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
     * @var string
     */
    public $group;

    /**
     * @var bool
     */
    public $showAppLoggerTab = false;

    /**
     * @var string
     */
    public $linkGeneratorReference;

    /**
     * @var array
     */
    public $compositeIndices = [];

    /**
     * @var bool
     */
    public $generateTypeDeclarations = false;

    /**
     * @var bool
     */
    public $showFieldLookup = false;

    /**
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
     * @var bool
     */
    public $enableGridLocking = false;

    /**
     * @param string $id
     * @param bool $force
     *
     * @return null|ClassDefinition
     *
     * @throws \Exception
     */
    public static function getById($id, $force = false)
    {
        if ($id === null) {
            throw new \Exception('Class id is null');
        }

        $cacheKey = 'class_' . $id;

        try {
            if ($force) {
                throw new \Exception('Forced load');
            }
            $class = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$class) {
                throw new \Exception('Class in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $class = new self();
                $name = $class->getDao()->getNameById($id);
                $definitionFile = \Pimcore::getContainer()->get(DefinitionPathResolverInterface::class)->resolvePath($name);
                $class = @include $definitionFile;

                if (!$class instanceof self) {
                    throw new \Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

                $class->setId($id);

                \Pimcore\Cache\Runtime::set($cacheKey, $class);
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
     * @param DataObject\ClassDefinition\Data|DataObject\ClassDefinition\Layout $data
     */
    public static function cleanupForExport(&$data)
    {
        if (isset($data->fieldDefinitionsCache)) {
            unset($data->fieldDefinitionsCache);
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
    public function exists()
    {
        $name = $this->getDao()->getNameById($this->getId());

        return is_string($name);
    }

    /**
     * @param bool $saveDefinitionFile
     *
     * @throws \Exception
     */
    public function save($saveDefinitionFile = true)
    {
        if (!$this->getId()) {
            $db = Db::get();
            $maxId = $db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM classes;');
            $this->setId($maxId ? $maxId + 1 : 1);
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
            \Pimcore::getEventDispatcher()->dispatch(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_ADD);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_UPDATE);
        }

        $this->setModificationDate(time());

        $this->getDao()->save($isUpdate);

        //TODO: Move to Controller? Only place this is needed inside Pimcore
        //For other use cases, the service can be included and used instead
        $this->generateClassFiles($saveDefinitionFile);

        // empty object cache
        try {
            Cache::clearTag('class_'.$this->getId());
        } catch (\Exception $e) {
        }

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::POST_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::POST_ADD);
        }
    }

    /**
     * @param bool $generateDefinitionFile
     *
     * @throws \Exception
     * @deprecated Deprecated in Pimcore 6.9, will be removed with 10.0. Use Pimcore\DataObject\ClassDefinition\PHPFileGeneratorInterface instead
     */
    public function generateClassFiles($generateDefinitionFile = true)
    {
        \Pimcore::getContainer()->get(PHPFileGeneratorInterface::class)->generatePHPFile($this);

        if ($generateDefinitionFile) {
            \Pimcore::getContainer()->get(DefinitionFileGeneratorInterface::class)->generateDefinitionFile($this);
        }
    }

    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_DELETE);

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
        $customLayouts->setCondition('classId = ?', $this->getId());
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

        \Pimcore::getEventDispatcher()->dispatch(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::POST_DELETE);
    }

    /**
     * Deletes PHP files from Filesystem
     *
     * @deprecated Deprecated in Pimcore 6.9, will be removed with 10.0. Use Pimcore\DataObject\ClassDefinition\DeleterInterface instead
     */
    protected function deletePhpClasses()
    {
        \Pimcore::getContainer()->get(DeleterInterface::class)->deleteClassDefinition($this);
    }

    /**
     * @param string|null $name
     *
     * @return string
     * @deprecated Deprecated in Pimcore 6.9, will be removed with 10.0. Use Pimcore\DataObject\ClassDefinition\DefinitionPathResolverInterface instead
     */
    public function getDefinitionFile($name = null)
    {
        if (!$name) {
            $name = $this->getName();
        }

        return \Pimcore::getContainer()->get(DefinitionPathResolverInterface::class)->resolvePath($name);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @return int
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @return int
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
        if (is_array($this->fieldDefinitions)) {
            foreach ($this->fieldDefinitions as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    protected function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if (method_exists($fieldDefinition, 'enrichFieldDefinition')) {
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
    public function setFieldDefinitions($fieldDefinitions)
    {
        $this->fieldDefinitions = is_array($fieldDefinitions) ? $fieldDefinitions : [];

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
        $this->layoutDefinitions = $layoutDefinitions;

        $this->fieldDefinitions = [];
        $this->extractDataDefinitions($this->layoutDefinitions);

        return $this;
    }

    /**
     * @param DataObject\ClassDefinition\Layout|DataObject\ClassDefinition\Data $def
     */
    public function extractDataDefinitions($def)
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
     * @param array $tables
     */
    public function addEncryptedTables(array $tables)
    {
        $this->encryptedTables = array_merge($this->encryptedTables, $tables);
        array_unique($this->encryptedTables);
    }

    /**
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
        $this->allowVariants = (bool)$allowVariants ? true : null;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
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
     * @param string $previewUrl
     *
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
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
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
     * @param array $compositeIndices
     *
     * @return $this
     */
    public function setCompositeIndices($compositeIndices)
    {
        $this->compositeIndices = $compositeIndices ?? [];

        return $this;
    }

    /**
     * @return bool
     */
    public function getGenerateTypeDeclarations()
    {
        return (bool) $this->generateTypeDeclarations;
    }

    /**
     * @param bool $generateTypeDeclarations
     *
     * @return $this
     */
    public function setGenerateTypeDeclarations($generateTypeDeclarations)
    {
        $this->generateTypeDeclarations = (bool) $generateTypeDeclarations;

        return $this;
    }
}
