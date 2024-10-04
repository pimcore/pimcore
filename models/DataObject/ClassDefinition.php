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

use Exception;
use Pimcore;
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
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\Dao getDao()
 */
final class ClassDefinition extends Model\AbstractModel implements ClassDefinitionInterface
{
    use DataObject\ClassDefinition\Helper\VarExport;
    use DataObject\Traits\LocateFileTrait;
    use DataObject\Traits\FieldDefinitionEnrichmentModelTrait;
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @internal
     */
    public ?string $id = null;

    /**
     * @internal
     */
    public ?string $name = null;

    /**
     * @internal
     */
    public string $title = '';

    /**
     * @internal
     */
    public string $description = '';

    /**
     * @internal
     */
    public ?int $creationDate = null;

    /**
     * @internal
     */
    public ?int $modificationDate = null;

    /**
     * @internal
     */
    public ?int $userOwner = null;

    /**
     * @internal
     */
    public ?int $userModification = null;

    /**
     * @internal
     */
    public string $parentClass = '';

    /**
     * Comma separated list of interfaces
     *
     * @internal
     */
    public ?string $implementsInterfaces = null;

    /**
     * Name of the listing parent class if set
     *
     * @internal
     */
    public string $listingParentClass = '';

    /**
     * @internal
     */
    public string $useTraits = '';

    /**
     * @internal
     */
    public string $listingUseTraits = '';

    /**
     * @internal
     */
    protected bool $encryption = false;

    /**
     * @internal
     */
    protected array $encryptedTables = [];

    /**
     * @internal
     */
    public bool $allowInherit = false;

    /**
     * @internal
     */
    public bool $allowVariants = false;

    /**
     * @internal
     */
    public bool $showVariants = false;

    /**
     * @internal
     */
    public ?ClassDefinition\Layout $layoutDefinitions = null;

    /**
     * @internal
     */
    public ?string $icon = null;

    /**
     * @internal
     */
    public ?string $group = null;

    /**
     * @internal
     */
    public bool $showAppLoggerTab = false;

    /**
     * @internal
     */
    public ?string $linkGeneratorReference = null;

    /**
     * @internal
     */
    public ?string $previewGeneratorReference = null;

    /**
     * @internal
     */
    public array $compositeIndices = [];

    /**
     * @internal
     */
    public bool $showFieldLookup = false;

    /**
     * @internal
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
     */
    public bool $enableGridLocking = false;

    /**
     * @var ClassDefinition\Data[]
     */
    private array $deletedDataComponents = [];

    /**
     * @throws Exception
     */
    public static function getById(string $id, bool $force = false): ?ClassDefinition
    {
        $cacheKey = 'class_' . $id;

        try {
            if ($force) {
                throw new Exception('Forced load');
            }
            $class = RuntimeCache::get($cacheKey);
            if (!$class) {
                throw new Exception('Class in registry is null');
            }
        } catch (Exception $e) {
            try {
                $class = new self();
                $name = $class->getDao()->getNameById($id);
                if (!$name) {
                    throw new Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

                $definitionFile = $class->getDefinitionFile($name);
                $class = @include $definitionFile;

                if (!$class instanceof self) {
                    throw new Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
                }

                $class->setId($id);

                RuntimeCache::set($cacheKey, $class);
            } catch (Exception $e) {
                Logger::info($e->getMessage());

                return null;
            }
        }

        return $class;
    }

    /**
     * @throws Exception
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

            if (!empty($data->getBlockedVarsForExport())) {
                $data->setBlockedVarsForExport([]);
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

    public function save(bool $saveDefinitionFile = true): void
    {
        if ($saveDefinitionFile && !$this->isWritable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd->isForbiddenName()) {
                throw new Exception(sprintf('Forbidden name used for field definition: %s', $fd->getName()));
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
            throw new Exception(sprintf('Invalid name for class definition: %s', $this->getName()));
        }

        if (!preg_match('/[a-zA-Z0-9]([a-zA-Z0-9_]+)?/', $this->getId())) {
            throw new Exception(sprintf('Invalid ID `%s` for class definition %s', $this->getId(), $this->getName()));
        }

        foreach (['parentClass', 'listingParentClass', 'useTraits', 'listingUseTraits'] as $propertyName) {
            $propertyValue = $this->{'get'.ucfirst($propertyName)}();
            if ($propertyValue && !preg_match('/^[a-zA-Z_\x7f-\xff\\\][a-zA-Z0-9_\x7f-\xff\\\ ,]*$/', $propertyValue)) {
                throw new Exception(sprintf('Invalid %s value for class definition: %s', $propertyName,
                    $this->getParentClass()));
            }
        }

        $isUpdate = $this->exists();

        if (!$isUpdate) {
            $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_ADD);
        } else {
            $this->dispatchEvent(new ClassDefinitionEvent($this), DataObjectClassDefinitionEvents::PRE_UPDATE);
        }

        // if definition file is not saved, modification date should not be updated
        if ($saveDefinitionFile) {
            $this->setModificationDate(time());
        }

        $this->getDao()->save($isUpdate);

        $this->generateClassFiles($saveDefinitionFile);

        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof \Pimcore\Model\DataObject\ClassDefinition\Data\ClassSavedInterface) {
                $fd->classSaved($this);
            }
        }

        // empty object cache
        try {
            Cache::clearTag('class_'.$this->getId());
        } catch (Exception $e) {
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
     * @throws Exception
     *
     * @internal
     */
    public function generateClassFiles(bool $generateDefinitionFile = true): void
    {
        Pimcore::getContainer()->get(PHPClassDumperInterface::class)->dumpPHPClasses($this);

        if ($generateDefinitionFile) {
            // save definition as a php file
            $definitionFile = $this->getDefinitionFile();
            if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
                throw new Exception(
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

        $fieldDefinitionDocBlockBuilder = Pimcore::getContainer()->get(FieldDefinitionDocBlockBuilderInterface::class);
        foreach ($this->getFieldDefinitions() as $fieldDefinition) {
            $cd .= ' * ' . str_replace("\n", "\n * ", trim($fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $cd .= ' */';

        return $cd;
    }

    public function delete(): void
    {
        if (!$this->isWritable() && file_exists($this->getDefinitionFile())) {
            throw new DataObject\Exception\DefinitionWriteException();
        }
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
        } catch (Exception $e) {
        }

        // empty output cache
        try {
            Cache::clearTag('output');
        } catch (Exception $e) {
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
            foreach ($classDefinitions as $key => $classDefinition) {
                if ($classDefinition['classname'] == $this->getId()) {
                    unset($classDefinitions[$key]);
                    $modified = true;
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

    /**
     * @return $this
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCreationDate(?int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return $this
     */
    public function setModificationDate(?int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUserOwner(?int $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
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

    /**
     * @return $this
     */
    public function setUseTraits(string $useTraits): static
    {
        $this->useTraits = $useTraits;

        return $this;
    }

    public function getListingUseTraits(): string
    {
        return $this->listingUseTraits;
    }

    /**
     * @return $this
     */
    public function setListingUseTraits(string $listingUseTraits): static
    {
        $this->listingUseTraits = $listingUseTraits;

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

    /**
     * @return $this
     */
    public function setParentClass(string $parentClass): static
    {
        $this->parentClass = (string) $parentClass;

        return $this;
    }

    /**
     * @return $this
     */
    public function setListingParentClass(string $listingParentClass): static
    {
        $this->listingParentClass = $listingParentClass;

        return $this;
    }

    public function getEncryption(): bool
    {
        return $this->encryption;
    }

    /**
     * @return $this
     */
    public function setEncryption(bool $encryption): static
    {
        $this->encryption = $encryption;

        return $this;
    }

    /**
     * @internal
     */
    public function addEncryptedTables(array $tables): void
    {
        $this->encryptedTables = array_unique(array_merge($this->encryptedTables, $tables));
    }

    /**
     * @internal
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
     * @return $this
     */
    public function setEncryptedTables(array $encryptedTables): static
    {
        $this->encryptedTables = $encryptedTables;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAllowInherit(bool $allowInherit): static
    {
        $this->allowInherit = $allowInherit;

        return $this;
    }

    /**
     * @return $this
     */
    public function setAllowVariants(bool $allowVariants): static
    {
        $this->allowVariants = $allowVariants;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return $this
     */
    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getPropertyVisibility(): array
    {
        return $this->propertyVisibility;
    }

    /**
     * @return $this
     */
    public function setPropertyVisibility(array $propertyVisibility): static
    {
        $this->propertyVisibility = $propertyVisibility;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDescription(string $description): static
    {
        $this->description = (string) $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setShowVariants(bool $showVariants): static
    {
        $this->showVariants = $showVariants;

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

    /**
     * @return $this
     */
    public function setShowAppLoggerTab(bool $showAppLoggerTab): static
    {
        $this->showAppLoggerTab = $showAppLoggerTab;

        return $this;
    }

    public function getShowFieldLookup(): bool
    {
        return $this->showFieldLookup;
    }

    /**
     * @return $this
     */
    public function setShowFieldLookup(bool $showFieldLookup): static
    {
        $this->showFieldLookup = $showFieldLookup;

        return $this;
    }

    public function getLinkGeneratorReference(): ?string
    {
        return $this->linkGeneratorReference;
    }

    /**
     * @return $this
     */
    public function setLinkGeneratorReference(?string $linkGeneratorReference): static
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

    /**
     * @return $this
     */
    public function setImplementsInterfaces(?string $implementsInterfaces): static
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }

    public function getCompositeIndices(): array
    {
        return $this->compositeIndices;
    }

    /**
     * @return $this
     */
    public function setCompositeIndices(array $compositeIndices): static
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
        $this->compositeIndices = $compositeIndices;

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
            if ($layoutDefinition === null) {
                continue;
            }
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
            if ($name === null) {
                throw new Exception('Class definition with ID ' . $id . ' does not exist');
            }
            $definitionFile = $class->getDefinitionFile($name);
            $class = @include $definitionFile;

            if (!$class instanceof self) {
                throw new Exception('Class definition with name ' . $name . ' or ID ' . $id . ' does not exist');
            }

            $class->setId($id);
        } catch (Exception $e) {
            Logger::info($e->getMessage());

            return null;
        }

        return $class;
    }
}
