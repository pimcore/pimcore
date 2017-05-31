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
 * @package    Object\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Fieldcollection;

use Pimcore\File;
use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @method \Pimcore\Model\Object\Fieldcollection\Definition\Dao getDao()
 */
class Definition extends Model\AbstractModel
{
    use Model\Object\ClassDefinition\Helper\VarExport;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $parentClass;

    /**
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentClass()
    {
        return $this->parentClass;
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
     * @return array
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param array $layoutDefinitions
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
     * @param array $context additional contextual data
     * @return array
     */
    public function getFieldDefinitions($context = array())
    {
        if (isset($context["suppressEnrichment"]) && $context["suppressEnrichment"]) {
            return $this->fieldDefinitions;
        }

        $enrichedFieldDefinitions = array();
        if (is_array($this->fieldDefinitions)) {
            foreach ($this->fieldDefinitions as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    /**
     * @param array $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions($fieldDefinitions)
    {
        $this->fieldDefinitions = $fieldDefinitions;

        return $this;
    }

    /**
     * @param string $key
     * @param Object\ClassDefinition\Data $data
     *
     * @return $this
     */
    public function addFieldDefinition($key, $data)
    {
        $this->fieldDefinitions[$key] = $data;

        return $this;
    }

    /**
     * @param $key
     * @param array $context additional contextual data
     * @return Object\ClassDefinition\Data|bool
     */
    public function getFieldDefinition($key, $context = array())
    {
        if (array_key_exists($key, $this->fieldDefinitions)) {
            if (isset($context["suppressEnrichment"]) && $context["suppressEnrichment"]) {
                return $this->fieldDefinitions[$key];
            }

            $fieldDefinition = $this->doEnrichFieldDefinition($this->fieldDefinitions[$key], $context);
            return $fieldDefinition;
        }

        return false;
    }

    public function doEnrichFieldDefinition($fieldDefinition, $context = array()) {
        if (method_exists($fieldDefinition, "enrichFieldDefinition")) {
            $context["containerType"] = "fieldcollection";
            $context["containerKey"] = $this->getKey();
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }
        return $fieldDefinition;
    }

    /**
     * @param array|Object\ClassDefinition\Layout|Object\ClassDefinition\Data $def
     */
    public function extractDataDefinitions($def)
    {
        if ($def instanceof Object\ClassDefinition\Layout) {
            if ($def->hasChildren()) {
                foreach ($def->getChildren() as $child) {
                    $this->extractDataDefinitions($child);
                }
            }
        }

        if ($def instanceof Object\ClassDefinition\Data) {
            $this->addFieldDefinition($def->getName(), $def);
        }
    }

    /**
     * @param $key
     *
     * @throws \Exception
     */
    public static function getByKey($key)
    {
        /** @var $fc Definition */
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

        throw new \Exception('Field-Collection with key: ' . $key . ' does not exist.');
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->getKey()) {
            throw new \Exception('A field-collection needs a key to be saved!');
        }

        $infoDocBlock = $this->getInfoDocBlock();

        $definitionFile = $this->getDefinitionFile();

        $clone = clone $this;
        $clone->setDao(null);
        unset($clone->fieldDefinitions);

        $exportedClass = var_export($clone, true);

        $data = '<?php ';
        $data .= "\n\n";
        $data .= $infoDocBlock;
        $data .= "\n\n";

        $data .= "\nreturn " . $exportedClass . ";\n";

        \Pimcore\File::put($definitionFile, $data);

        $extendClass = 'Object\\Fieldcollection\\Data\\AbstractData';
        if ($this->getParentClass()) {
            $extendClass = $this->getParentClass();
            $extendClass = '\\' . ltrim($extendClass, '\\');
        }

        // create class file
        $cd = '<?php ';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\Object\\Fieldcollection\\Data;';
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
                $cd .= $def->getGetterCodeFieldcollection($this);

                if ($def instanceof Object\ClassDefinition\Data\Localizedfields) {
                    foreach ($def->getFieldDefinitions() as $localizedFd) {

                        /**
                         * @var $fd Object\ClassDefinition\Data
                         */
                        $cd .= $localizedFd->getGetterCodeLocalizedfields($this);
                    }
                }

                $cd .= $def->getSetterCodeFieldcollection($this);

                if ($def instanceof Object\ClassDefinition\Data\Localizedfields) {
                    foreach ($def->getFieldDefinitions() as $localizedFd) {

                        /**
                         * @var $fd Object\ClassDefinition\Data
                         */
                        $cd .= $localizedFd->getSetterCodeLocalizedfields($this);
                    }
                }
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        File::put($this->getPhpClassFile(), $cd);

        // update classes
        $classList = new Object\ClassDefinition\Listing();
        $classes = $classList->load();
        if (is_array($classes)) {
            foreach ($classes as $class) {
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if ($fieldDef instanceof Object\ClassDefinition\Data\Fieldcollections) {
                        if (in_array($this->getKey(), $fieldDef->getAllowedTypes())) {
                            $this->getDao()->createUpdateTable($class);
                            break;
                        }
                    }
                }
            }
        }
    }

    public function delete()
    {
        @unlink($this->getDefinitionFile());
        @unlink($this->getPhpClassFile());

        // update classes
        $classList = new Object\ClassDefinition\Listing();
        $classes = $classList->load();
        if (is_array($classes)) {
            foreach ($classes as $class) {
                foreach ($class->getFieldDefinitions() as $fieldDef) {
                    if ($fieldDef instanceof Object\ClassDefinition\Data\Fieldcollections) {
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
        $classFolder = PIMCORE_CLASS_DIRECTORY . '/Object/Fieldcollection/Data';
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
        $cd .= '* Generated at: ' . date('c') . "\n";

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $cd .= '* IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
        }

        $cd .= "\n\n";
        $cd .= "Fields Summary: \n";

        if (is_array($this->getFieldDefinitions())) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $cd .= ' - ' . $fd->getName() . ' [' . $fd->getFieldtype() . "]\n";
            }
        }

        $cd .= '*/ ';

        return $cd;
    }
}
