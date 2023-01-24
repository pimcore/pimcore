<?php
declare(strict_types=1);

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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentModelInterface;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Dao getDao()
 */
final class ClassDefinition extends Model\AbstractModel implements FieldDefinitionEnrichmentModelInterface
{
    use DataObject\ClassDefinition\Helper\VarExport;
    use DataObject\Traits\LocateFileTrait;
    use DataObject\Traits\FieldDefinitionEnrichmentModelTrait;
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @internal
     *
     * @var string
     */
    public string $title = '';

    /**
     * @internal
     *
     * @var string
     */
    public string $description = '';

    /**
     * @internal
     *
     * @var int|null
     */
    public ?int $creationDate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    public ?int $modificationDate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    public ?int $userOwner = null;

    /**
     * @internal
     *
     * @var int|null
     */
    public ?int $userModification = null;

    /**
     * @internal
     *
     * @var string
     */
    public string $parentClass = '';

    /**
     * Comma separated list of interfaces
     *
     * @internal
     *
     * @var string|null
     */
    public ?string $implementsInterfaces = null;

    /**
     * Name of the listing parent class if set
     *
     * @internal
     *
     * @var string
     */
    public string $listingParentClass = '';

    /**
     * @internal
     *
     * @var string
     */
    public string $useTraits = '';

    /**
     * @internal
     *
     * @var string
     */
    public string $listingUseTraits = '';

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $encryption = false;

    /**
     * @internal
     *
     * @var array
     */
    protected array $encryptedTables = [];

    /**
     * @internal
     *
     * @var bool
     */
    public bool $allowInherit = false;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $allowVariants = false;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $showVariants = false;

    /**
     * @internal
     *
     * @var DataObject\ClassDefinition\Layout|null
     */
    public ?ClassDefinition\Layout $layoutDefinitions = null;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $icon = null;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $group = null;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $showAppLoggerTab = false;

    /**
     * @internal
     *
     * @var string
     */
    public string $linkGeneratorReference;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $previewGeneratorReference = null;

    /**
     * @internal
     *
     * @var array
     */
    public array $compositeIndices = [];

    /**
     * @internal
     *
     * @var bool
     */
    public bool $showFieldLookup = false;

    /**
     * @internal
     *
     * @var array
     */
    public array $propertyVisibility = [
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
    public bool $enableGridLocking = false;

    /**
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
    public static function getById(string $id, bool $force = false): ?ClassDefinition
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
                if (!$name) {
                    throw new \Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

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
    public static function getByName(string $name): ?ClassDefinition
    {
        try {
            $class = new self();
            $id = $class->getDao()->getIdByName($name);

            return self::getById($id);
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function create(array $values = []): ClassDefinition
    {
        $class = new self();
        $class->setValues($values);

        return $class;
    }

    /**
     * @param string $name
     *
     * @internal
     */
    public function rename(string $name): void
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
    public static function cleanupForExport(mixed &$data): void
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

    private function exists(): bool
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
    public function save(bool $saveDefinitionFile = true): void
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
    public function generateClassFiles(bool $generateDefinitionFile = true): void
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
            $clone->setFieldDefinitions([]);

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

        if ($title = $this->getTitle()) {
            $cd .= ' * Title: ' . $title."\n";
        }

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

    public function delete(): void
    {
        $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_DELETE);

        // delete all objects using this class
        $list = new Listing();
        $list->setCondition('classId = ?', $this->getId());
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

    private function deletePhpClasses(): void
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
     */
    public function isWritable(): bool
    {
        return (bool) ($_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE'] ?? !str_starts_with($this->getDefinitionFile(), PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY));
    }

    /**
     * @param string|null $name
     *
     * @return string
     *
     * @internal
     */
    public function getDefinitionFile(string $name = null): string
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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setCreationDate(?int $creationDate): static
    {
        $this->creationDate = (int)$creationDate;

        return $this;
    }

    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = (int)$modificationDate;

        return $this;
    }

    public function setUserOwner(?int $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    public function setUserModification(?int $userModification): static
    {
        $this->userModification = $userModification;

        return $this;
    }

    /**
     * @internal
     */
    public function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['class'] = $this;
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    public function getLayoutDefinitions(): ?ClassDefinition\Layout
    {
        return $this->layoutDefinitions;
    }

    public function setLayoutDefinitions(?ClassDefinition\Layout $layoutDefinitions): static
    {
        $oldFieldDefinitions = null;
        if ($this->layoutDefinitions !== null) {
            $this->setDeletedDataComponents([]);
            $oldFieldDefinitions = $this->getFieldDefinitions();
        }

        $this->layoutDefinitions = $layoutDefinitions;

        $this->setFieldDefinitions([]);
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

    private function extractDataDefinitions(ClassDefinition\Data|ClassDefinition\Layout|null $def): void
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

    public function getParentClass(): string
    {
        return $this->parentClass;
    }

    public function getListingParentClass(): string
    {
        return $this->listingParentClass;
    }

    public function getUseTraits(): string
    {
        return $this->useTraits;
    }

    public function setUseTraits(string $useTraits): static
    {
        $this->useTraits = (string) $useTraits;

        return $this;
    }

    public function getListingUseTraits(): string
    {
        return $this->listingUseTraits;
    }

    public function setListingUseTraits(string $listingUseTraits): static
    {
        $this->listingUseTraits = (string) $listingUseTraits;

        return $this;
    }

    public function getAllowInherit(): bool
    {
        return $this->allowInherit;
    }

    public function getAllowVariants(): bool
    {
        return $this->allowVariants;
    }

    public function setParentClass(string $parentClass): static
    {
        $this->parentClass = $parentClass;

        return $this;
    }

    public function setListingParentClass(string $listingParentClass): static
    {
        $this->listingParentClass = (string) $listingParentClass;

        return $this;
    }

    public function getEncryption(): bool
    {
        return $this->encryption;
    }

    public function setEncryption(bool $encryption): static
    {
        $this->encryption = $encryption;

        return $this;
    }

    /**
     * @internal
     *
     * @param array $tables
     */
    public function addEncryptedTables(array $tables): void
    {
        $this->encryptedTables = array_unique(array_merge($this->encryptedTables, $tables));
    }

    /**
     * @internal
     *
     * @param array $tables
     */
    public function removeEncryptedTables(array $tables): void
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
    public function setEncryptedTables(array $encryptedTables): static
    {
        $this->encryptedTables = $encryptedTables;

        return $this;
    }

    public function setAllowInherit(bool $allowInherit): static
    {
        $this->allowInherit = (bool)$allowInherit;

        return $this;
    }

    public function setAllowVariants(bool $allowVariants): static
    {
        $this->allowVariants = (bool)$allowVariants;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getPropertyVisibility(): array
    {
        return $this->propertyVisibility;
    }

    public function setPropertyVisibility(array $propertyVisibility): static
    {
        if (is_array($propertyVisibility)) {
            $this->propertyVisibility = $propertyVisibility;
        }

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setShowVariants(bool $showVariants): static
    {
        $this->showVariants = (bool)$showVariants;

        return $this;
    }

    public function getShowVariants(): bool
    {
        return $this->showVariants;
    }

    public function getShowAppLoggerTab(): bool
    {
        return $this->showAppLoggerTab;
    }

    public function setShowAppLoggerTab(bool $showAppLoggerTab): static
    {
        $this->showAppLoggerTab = (bool) $showAppLoggerTab;

        return $this;
    }

    public function getShowFieldLookup(): bool
    {
        return $this->showFieldLookup;
    }

    public function setShowFieldLookup(bool $showFieldLookup): static
    {
        $this->showFieldLookup = (bool) $showFieldLookup;

        return $this;
    }

    public function getLinkGeneratorReference(): string
    {
        return $this->linkGeneratorReference;
    }

    public function setLinkGeneratorReference(string $linkGeneratorReference): static
    {
        $this->linkGeneratorReference = $linkGeneratorReference;

        return $this;
    }

    public function getLinkGenerator(): ?ClassDefinition\LinkGeneratorInterface
    {
        /** @var ClassDefinition\LinkGeneratorInterface $interface */
        $interface = DataObject\ClassDefinition\Helper\LinkGeneratorResolver::resolveGenerator($this->getLinkGeneratorReference());

        return $interface;
    }

    public function getPreviewGeneratorReference(): ?string
    {
        return $this->previewGeneratorReference;
    }

    public function setPreviewGeneratorReference(?string $previewGeneratorReference): void
    {
        $this->previewGeneratorReference = $previewGeneratorReference;
    }

    public function getPreviewGenerator(): ?ClassDefinition\PreviewGeneratorInterface
    {
        $interface = null;

        if ($this->getPreviewGeneratorReference()) {
            /** @var ClassDefinition\PreviewGeneratorInterface $interface */
            $interface = DataObject\ClassDefinition\Helper\PreviewGeneratorResolver::resolveGenerator($this->getPreviewGeneratorReference());
        }

        return $interface;
    }

    public function isEnableGridLocking(): bool
    {
        return $this->enableGridLocking;
    }

    public function setEnableGridLocking(bool $enableGridLocking): void
    {
        $this->enableGridLocking = $enableGridLocking;
    }

    public function getImplementsInterfaces(): ?string
    {
        return $this->implementsInterfaces;
    }

    public function setImplementsInterfaces(?string $implementsInterfaces): static
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }

    public function getCompositeIndices(): array
    {
        return $this->compositeIndices;
    }

    public function setCompositeIndices(?array $compositeIndices): static
    {
        $class = $this->getFieldDefinitions([]);
        foreach ($compositeIndices as $indexInd => $compositeIndex) {
            foreach ($compositeIndex['index_columns'] as $fieldInd => $fieldName) {
                if (isset($class[$fieldName]) && $class[$fieldName] instanceof ManyToOneRelation) {
                    $compositeIndices[$indexInd]['index_columns'][$fieldInd] = $fieldName . '__id';
                    $compositeIndices[$indexInd]['index_columns'][] = $fieldName . '__type';
                    $compositeIndices[$indexInd]['index_columns'] = array_unique($compositeIndices[$indexInd]['index_columns']);
                }
            }
        }
        $this->compositeIndices = $compositeIndices ?? [];

        return $this;
    }

    /**
     * @return ClassDefinition\Data[]
     */
    public function getDeletedDataComponents(): array
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
