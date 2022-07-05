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

namespace Pimcore\Model\DataObject\Fieldcollection;

use Pimcore\DataObject\ClassBuilder\FieldDefinitionDocBlockBuilderInterface;
use Pimcore\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\FieldDefinitionEnrichmentInterface;

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
            $context['containerType'] = 'fieldcollection';
            $context['containerKey'] = $this->getKey();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @internal
     *
     * @param DataObject\ClassDefinition\Layout|DataObject\ClassDefinition\Data $def
     */
    protected function extractDataDefinitions($def)
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
     * @param string $key
     *
     * @throws \Exception
     *
     * @return self|null
     */
    public static function getByKey($key)
    {
        /** @var Definition $fc */
        $fc = null;
        $cacheKey = 'fieldcollection_' . $key;

        try {
            $fc = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$fc) {
                throw new \Exception('FieldCollection in registry is not valid');
            }
        } catch (\Exception $e) {
            $def = new Definition();
            $def->setKey($key);
            $fieldFile = $def->getDefinitionFile();

            if (is_file($fieldFile)) {
                $fc = include $fieldFile;
                \Pimcore\Cache\Runtime::set($cacheKey, $fc);
            }
        }

        if ($fc) {
            return $fc;
        }

        return null;
    }

    /**
     * @param bool $saveDefinitionFile
     *
     * @throws \Exception
     */
    public function save($saveDefinitionFile = true)
    {
        if (!$this->getKey()) {
            throw new \Exception('A field-collection needs a key to be saved!');
        }

        if (!preg_match('/[a-zA-Z]+/', $this->getKey())) {
            throw new \Exception(sprintf('Invalid key for field-collection: %s', $this->getKey()));
        }

        if ($this->getParentClass() && !preg_match('/^[a-zA-Z_\x7f-\xff\\\][a-zA-Z0-9_\x7f-\xff\\\]*$/', $this->getParentClass())) {
            throw new \Exception(sprintf('Invalid parentClass value for class definition: %s',
                $this->getParentClass()));
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

        $this->generateClassFiles($saveDefinitionFile);

        // update classes
        $classList = new DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        if (is_array($classes)) {
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
        }
    }

    /**
     * @internal
     *
     * @param bool $generateDefinitionFile
     *
     * @throws \Exception
     * @throws DataObject\Exception\DefinitionWriteException
     */
    protected function generateClassFiles($generateDefinitionFile = true)
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

            \Pimcore\File::put($definitionFile, $data);
        }

        \Pimcore::getContainer()->get(PHPFieldCollectionClassDumperInterface::class)->dumpPHPClass($this);

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\DataContainerAwareInterface) {
                $fd->postSave($this);
            }
        }
    }

    public function delete()
    {
        @unlink($this->getDefinitionFile());
        @unlink($this->getPhpClassFile());

        // update classes
        $classList = new DataObject\ClassDefinition\Listing();
        $classes = $classList->load();
        if (is_array($classes)) {
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
        }
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
        return $this->locateDefinitionFile($key ?? $this->getKey(), 'fieldcollections/%s.php');
    }

    /**
     * @internal
     *
     * @return string
     */
    public function getPhpClassFile()
    {
        return $this->locateFile(ucfirst($this->getKey()), 'DataObject/Fieldcollection/Data/%s.php');
    }

    /**
     * @internal
     *
     * @return string
     */
    protected function getInfoDocBlock(): string
    {
        $cd = '/**' . "\n";
        $cd .= " * Fields Summary:\n";

        $fieldDefinitionDocBlockBuilder = \Pimcore::getContainer()->get(FieldDefinitionDocBlockBuilderInterface::class);
        foreach ($this->getFieldDefinitions() as $fieldDefinition) {
            $cd .= ' * ' . str_replace("\n", "\n * ", trim($fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $cd .= ' */';

        return $cd;
    }
}
