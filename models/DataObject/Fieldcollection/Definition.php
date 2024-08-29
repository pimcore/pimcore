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

namespace Pimcore\Model\DataObject\Fieldcollection;

use Exception;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\DataObject\ClassBuilder\FieldDefinitionDocBlockBuilderInterface;
use Pimcore\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use Pimcore\Event\FieldcollectionDefinitionEvents;
use Pimcore\Event\Model\DataObject\FieldcollectionDefinitionEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @method \Pimcore\Model\DataObject\Fieldcollection\Definition\Dao getDao()
 * @method string getTableName(DataObject\ClassDefinition $class)
 * @method void createUpdateTable(DataObject\ClassDefinition $class)
 * @method string getLocalizedTableName(DataObject\ClassDefinition $class)
 */
class Definition extends Model\AbstractModel
{
    use DataObject\Traits\FieldcollectionObjectbrickDefinitionTrait;
    use DataObject\Traits\LocateFileTrait;
    use Model\DataObject\ClassDefinition\Helper\VarExport;
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @var string[]
     */
    protected const FORBIDDEN_NAMES = [
        'abstract', 'abstractdata', 'class', 'concrete', 'dao', 'data', 'default', 'folder', 'interface', 'items',
        'list', 'object', 'permissions', 'resource',
    ];

    protected function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['containerType'] = 'fieldcollection';
            $context['containerKey'] = $this->getKey();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @internal
     */
    protected function extractDataDefinitions(DataObject\ClassDefinition\Data|DataObject\ClassDefinition\Layout $def): void
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
     * @throws Exception
     */
    public static function getByKey(string $key): ?Definition
    {
        /** @var Definition $fc */
        $fc = null;
        $cacheKey = 'fieldcollection_' . $key;

        try {
            $fc = RuntimeCache::get($cacheKey);
            if (!$fc) {
                throw new Exception('FieldCollection in registry is not valid');
            }
        } catch (Exception $e) {
            $def = new Definition();
            $def->setKey($key);
            $fieldFile = $def->getDefinitionFile();

            if (is_file($fieldFile)) {
                $fc = include $fieldFile;
                RuntimeCache::set($cacheKey, $fc);
            }
        }

        if ($fc) {
            return $fc;
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function save(bool $saveDefinitionFile = true): void
    {
        if (!$this->getKey()) {
            throw new Exception('A field-collection needs a key to be saved!');
        }

        if ($this->isForbiddenName()) {
            throw new Exception(sprintf('Invalid key for field-collection: %s', $this->getKey()));
        }

        if ($this->getParentClass() && !preg_match('/^[a-zA-Z_\x7f-\xff\\\][a-zA-Z0-9_\x7f-\xff\\\]*$/', $this->getParentClass())) {
            throw new Exception(sprintf('Invalid parentClass value for class definition: %s',
                $this->getParentClass()));
        }

        $isUpdate = file_exists($this->getDefinitionFile());

        if (!$isUpdate) {
            $this->dispatchEvent(new FieldcollectionDefinitionEvent($this), FieldcollectionDefinitionEvents::PRE_ADD);
        } else {
            $this->dispatchEvent(new FieldcollectionDefinitionEvent($this), FieldcollectionDefinitionEvents::PRE_UPDATE);
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

        $this->generateClassFiles($saveDefinitionFile);

        // update classes
        $classList = new DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        foreach ($classes as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDef) {
                if ($fieldDef instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                    if (in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                        $this->getDao()->createUpdateTable($class);

                        break;
                    }
                }
            }
        }

        if (!$isUpdate) {
            $this->dispatchEvent(new FieldcollectionDefinitionEvent($this), FieldcollectionDefinitionEvents::POST_ADD);
        } else {
            $this->dispatchEvent(new FieldcollectionDefinitionEvent($this), FieldcollectionDefinitionEvents::POST_UPDATE);
        }
    }

    /**
     * @throws Exception
     * @throws DataObject\Exception\DefinitionWriteException
     *
     * @internal
     */
    protected function generateClassFiles(bool $generateDefinitionFile = true): void
    {
        if ($generateDefinitionFile && !$this->isWritable()) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $definitionFile = $this->getDefinitionFile();

        if ($generateDefinitionFile) {
            /** @var self $clone */
            $clone = DataObject\Service::cloneDefinition($this);
            $clone->setDao(null);
            unset($clone->fieldDefinitions);
            DataObject\ClassDefinition::cleanupForExport($clone->layoutDefinitions);

            $exportedClass = var_export($clone, true);

            $data = '<?php';
            $data .= "\n\n";
            $data .=  $this->getInfoDocBlock();
            $data .= "\n\n";

            $data .= 'return ' . $exportedClass . ";\n";

            $filesystem = new Filesystem();
            $filesystem->dumpFile($definitionFile, $data);
        }

        Pimcore::getContainer()->get(PHPFieldCollectionClassDumperInterface::class)->dumpPHPClass($this);

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->postSave($this);
            }
        }
    }

    /**
     * @throws DataObject\Exception\DefinitionWriteException
     */
    public function delete(): void
    {
        if (!$this->isWritable() && file_exists($this->getDefinitionFile())) {
            throw new DataObject\Exception\DefinitionWriteException();
        }

        $this->dispatchEvent(new FieldcollectionDefinitionEvent($this), FieldcollectionDefinitionEvents::PRE_DELETE);

        @unlink($this->getDefinitionFile());
        @unlink($this->getPhpClassFile());

        // update classes
        $classList = new DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        foreach ($classes as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDef) {
                if ($fieldDef instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                    if (in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                        $this->getDao()->delete($class);

                        break;
                    }
                }
            }
        }

        $this->dispatchEvent(new FieldcollectionDefinitionEvent($this), FieldcollectionDefinitionEvents::POST_DELETE);
    }

    /**
     * @internal
     */
    public function isWritable(): bool
    {
        return (bool) ($_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE'] ?? !str_starts_with($this->getDefinitionFile(), PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY));
    }

    /**
     * @internal
     */
    public function getDefinitionFile(string $key = null): string
    {
        return $this->locateDefinitionFile($key ?? $this->getKey(), 'fieldcollections/%s.php');
    }

    /**
     * @internal
     */
    public function getPhpClassFile(): string
    {
        return $this->locateFile(ucfirst($this->getKey()), 'DataObject/Fieldcollection/Data/%s.php');
    }

    /**
     * @internal
     */
    protected function getInfoDocBlock(): string
    {
        $cd = '/**' . "\n";
        $cd .= " * Fields Summary:\n";

        $fieldDefinitionDocBlockBuilder = Pimcore::getContainer()->get(FieldDefinitionDocBlockBuilderInterface::class);
        foreach ($this->getFieldDefinitions() as $fieldDefinition) {
            $cd .= ' * ' . str_replace("\n", "\n * ", trim($fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $cd .= ' */';

        return $cd;
    }

    public function isForbiddenName(): bool
    {
        $key = $this->getKey();
        if ($key === null || $key === '') {
            return true;
        }
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $key)) {
            return true;
        }

        return in_array(strtolower($key), self::FORBIDDEN_NAMES);
    }
}
