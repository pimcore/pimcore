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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class CalculatedValue extends Data implements QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'calculatedValue';

    /** @var string */
    public $elementType = 'input';

    /**
     * @var int
     */
    public $width = 0;

    /**
     * @var string
     */
    public $calculatorClass;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'varchar';

    /**
     * Column length
     *
     * @var int
     */
    public $columnLength = 190;

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\CalculatedValue';

    /**
     * @return string
     */
    public function getElementType(): string
    {
        return $this->elementType;
    }

    /**
     * @param string $elementType
     *
     * @return $this
     */
    public function setElementType($elementType)
    {
        if ($elementType) {
            $this->elementType = $elementType;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = intval($width);
    }

    /**
     * @return int
     */
    public function getColumnLength()
    {
        return $this->columnLength;
    }

    /**
     * @param int|null $columnLength
     *
     * @return $this
     */
    public function setColumnLength($columnLength)
    {
        if ($columnLength) {
            $this->columnLength = $columnLength;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCalculatorClass()
    {
        return $this->calculatorClass;
    }

    /**
     * @param string $calculatorClass
     */
    public function setCalculatorClass($calculatorClass)
    {
        $this->calculatorClass = $calculatorClass;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param float $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param Model\DataObject\Data\CalculatedValue|null $data
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\CalculatedValue) {
            return Model\DataObject\Service::getCalculatedFieldValueForEditMode($object, $params, $data);
        }

        return $data;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param float $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\CalculatedValue|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return (string)$this->getDataForEditmode($data, $object, $params);
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param DataObject\Data\CalculatedValue|null $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        // nothing to do
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        return $this->getDataFromObjectParam($object, $params);
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        // nothing to do
        return null;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return $this->getDataFromObjectParam($object, $params);
    }

    /**
     * converts data to be imported via webservices
     *
     * @deprecated
     *
     * @param mixed $value
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        // nothing to do
    }

    /**
     * @return string
     */
    public function getQueryColumnType()
    {
        return $this->queryColumnType . '(' . $this->getColumnLength() . ')';
    }

    /**
     * Creates getter code which is used for generation of php file for object classes using this data type
     *
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getGetterCode($class)
    {
        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("object", null, null, null);' . "\n";

        if ($class instanceof DataObject\Objectbrick\Definition) {
            $code .= "\t" . '$object = $this->getObject();'  . "\n";
        } else {
            $code .= "\t" . '$object = $this;'  . "\n";
        }

        $code .= "\t" . '$data = \\Pimcore\\Model\\DataObject\\Service::getCalculatedFieldValue($object, $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for localized fields in classes using this data type
     *
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getGetterCodeLocalizedfields($class)
    {
        $key = $this->getName();
        $code = '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ($language = null) {' . "\n";
        $code .= "\t" . 'if (!$language) {' . "\n";
        $code .= "\t\t" . 'try {' . "\n";
        $code .= "\t\t\t" . '$locale = \Pimcore::getContainer()->get("pimcore.locale")->findLocale();'  . "\n";
        $code .= "\t\t\t" . 'if (\Pimcore\Tool::isValidLanguage($locale)) {'  . "\n";
        $code .= "\t\t\t\t" . '$language = (string) $locale;'  . "\n";
        $code .= "\t\t\t" . '} else {'  . "\n";
        $code .= "\t\t\t\t" . 'throw new \Exception("Not supported language");'  . "\n";
        $code .= "\t\t\t" . '}'  . "\n";
        $code .= "\t\t" . '} catch (\Exception $e) {' . "\n";
        $code .= "\t\t\t" . '$language = \Pimcore\Tool::getDefaultLanguage();' . "\n";
        $code .= "\t\t" . '}' . "\n";
        $code .= "\t" . '}'  . "\n";

        if ($class instanceof DataObject\Objectbrick\Definition) {
            $ownerType = 'objectbrick';
            $index = $class->getKey();
            $ownerName = '$this->getFieldName()';

            $code .= "\t" . '$object = $this->getObject();'  . "\n";
        } else {
            $ownerType = 'localizedfield';
            $ownerName = '"localizedfields"';
            $index = null;

            $code .= "\t" . '$object = $this;'  . "\n";
        }

        if ($class instanceof DataObject\Fieldcollection\Definition || $class instanceof DataObject\Objectbrick\Definition) {
            $code .= "\t" . '$fieldDefinition = $this->getDefinition()->getFieldDefinition("localizedfields")->getFieldDefinition("'.$key.'");'  . "\n";
        } else {
            $code .= "\t" . '$fieldDefinition = $this->getClass()->getFieldDefinition("localizedfields")->getFieldDefinition("'.$key.'");'  . "\n";
        }

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("'.$ownerType.'", ' . $ownerName . ', '.($index === null ? 'null' : '"'.$index.'"').', $language, null, null, $fieldDefinition);' . "\n";

        $code .= "\t" . '$data = \\Pimcore\\Model\\DataObject\\Service::getCalculatedFieldValue($object, $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for object brick classes using this data type
     *
     * @param DataObject\Objectbrick\Definition $brickClass
     *
     * @return string
     */
    public function getGetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();
        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ($language = null) {' . "\n";

        $code .= "\t" . '$brickDefinition = DataObject\Objectbrick\Definition::getByKey("' . $brickClass->getKey() . '");' . "\n";
        $code .= "\t" . '$fd = $brickDefinition->getFieldDefinition("' . $key . '");' . "\n";

        $code .= "\t" . '$data' . ' = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue($fd->getName());' . "\n";
        $code .= "\t" . '$data->setContextualData("objectbrick", $this->getFieldName() , $this->getType(), $fd->getName(), null, null, $fd);' . "\n";

        $code .= "\t" . '$data = DataObject\Service::getCalculatedFieldValue($this->getObject(), $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for fieldcollection classes using this data type
     *
     * @param DataObject\Fieldcollection\Definition $fieldcollectionDefinition
     *
     * @return string
     */
    public function getGetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue('" . $key . "');\n";

        $code .= "\t" . '$fcDef = DataObject\Fieldcollection\Definition::getByKey($this->getType());' . "\n";
        $code .= "\t" . '$definition = $fcDef->getFieldDefinition(\'' . $this->getName() . '\');' . "\n";

        $code .= "\t" . '$data->setContextualData("fieldcollection", $this->getFieldname(), $this->getIndex(), null, null, null, $definition);' . "\n";

        $code .= "\t" . '$data = DataObject\Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for object classes using this data type
     *
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getSetterCode($class)
    {
        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($class->getName()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for object brick classes using this data type
     *
     * @param DataObject\Objectbrick\Definition $brickClass
     *
     * @return string
     */
    public function getSetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickClass->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for fieldcollection classes using this data type
     *
     * @param DataObject\Fieldcollection\Definition $fieldcollectionDefinition
     *
     * @return string
     */
    public function getSetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($fieldcollectionDefinition->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for localized fields in classes using this data type
     *
     * @param DataObject\ClassDefinition $class
     *
     * @return string
     */
    public function getSetterCodeLocalizedfields($class)
    {
        $key = $this->getName();
        if ($class instanceof DataObject\Objectbrick\Definition) {
            $classname = 'Objectbrick\\Data\\' . ucfirst($class->getKey());
        } elseif ($class instanceof DataObject\Fieldcollection\Definition) {
            $classname = 'Fieldcollection\\Data\\' . ucfirst($class->getKey());
        } else {
            $classname = $class->getName();
        }

        $code = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($classname) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ', $language = null) {' . "\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return mixed
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $data;
    }

    public function supportsInheritance()
    {
        return false;
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        return $oldValue === $newValue;
    }
}
