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
 * @package    DataObject\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Fieldcollection;

use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\Fieldcollection\Definition\Dao getDao()
 * @method string getTableName(DataObject\ClassDefinition $class)
 * @method void createUpdateTable(DataObject\ClassDefinition $class)
 * @method string getLocalizedTableName(DataObject\ClassDefinition $class)
 */
class Definition extends Model\AbstractModel
{
    use DataObject\Traits\FieldcollectionObjectbrickDefinitionTrait;

    use Model\DataObject\ClassDefinition\Helper\VarExport;

    protected function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if (method_exists($fieldDefinition, 'enrichFieldDefinition')) {
            $context['containerType'] = 'fieldcollection';
            $context['containerKey'] = $this->getKey();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
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
            $fieldCollectionFolder = PIMCORE_CLASS_DIRECTORY . '/fieldcollections';
            $fieldFile = $fieldCollectionFolder . '/' . $key . '.php';

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
     * @param bool $generateDefinitionFile
     *
     * @throws \Exception
     */
    public function generateClassFiles($generateDefinitionFile = true)
    {
        $infoDocBlock = $this->getInfoDocBlock();

        $definitionFile = $this->getDefinitionFile();

        if ($generateDefinitionFile) {
            $clone = clone $this;
            $clone->setDao(null);
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

        $extendClass = 'DataObject\\Fieldcollection\\Data\\AbstractData';
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
            $extendClass = '\\' . ltrim($extendClass, '\\');
        }

        // create class file
        $cd = '<?php ';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject\\Fieldcollection\\Data;';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\\Model\\DataObject;';
        $cd .= "\n";
        $cd .= 'use Pimcore\Model\DataObject\PreGetValueHookInterface;';
        $cd .= "\n\n";

        $implementsParts = [];

        $implements = DataObject\ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $this->getImplementsInterfaces());

        $cd .= 'class ' . ucfirst($this->getKey()) . ' extends ' . $extendClass . $implements . ' {';

        $cd .= "\n\n";

        $cd .= 'protected $type = "' . $this->getKey() . "\";\n";

        if (is_array($this->getFieldDefinitions()) && count($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $key => $def) {
                $cd .= 'protected $' . $key . ";\n";
            }
        }

        $cd .= "\n\n";

        $fdDefs = $this->getFieldDefinitions();
        if (is_array($fdDefs) && count($fdDefs)) {
            foreach ($fdDefs as $key => $def) {
                $cd .= $def->getGetterCodeFieldcollection($this);

                if ($def instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getGetterCode($this);
                }

                $cd .= $def->getSetterCodeFieldcollection($this);

                if ($def instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getSetterCode($this);
                }
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        File::put($this->getPhpClassFile(), $cd);
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
     * @return string
     */
    protected function getDefinitionFile()
    {
        $fieldClassFolder = PIMCORE_CLASS_DIRECTORY . '/fieldcollections';
        $definitionFile = $fieldClassFolder . '/' . $this->getKey() . '.php';

        return $definitionFile;
    }

    /**
     * @return string
     */
    protected function getPhpClassFile()
    {
        $classFolder = PIMCORE_CLASS_DIRECTORY . '/DataObject/Fieldcollection/Data';
        $classFile = $classFolder . '/' . ucfirst($this->getKey()) . '.php';

        return $classFile;
    }

    /**
     * @return string
     */
    protected function getInfoDocBlock()
    {
        $cd = '';

        $cd .= '/** ';
        $cd .= "\n";
        $cd .= "Fields Summary: \n";

        $cd = $this->getInfoDocBlockForFields($this, $cd, 1);

        $cd .= '*/ ';

        return $cd;
    }

    /**
     * @param Definition|DataObject\ClassDefinition\Data $definition
     * @param string $text
     * @param int $level
     *
     * @return string
     */
    protected function getInfoDocBlockForFields($definition, $text, $level)
    {
        if (is_array($definition->getFieldDefinitions())) {
            foreach ($definition->getFieldDefinitions() as $fd) {
                $text .= str_pad('', $level, '-') . ' ' . $fd->getName() . ' [' . $fd->getFieldtype() . "]\n";
                if (method_exists($fd, 'getFieldDefinitions')) {
                    $text = $this->getInfoDocBlockForFields($fd, $text, $level + 1);
                }
            }
        }

        return $text;
    }
}
