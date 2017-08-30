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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

class CalculatedValue extends Model\DataObject\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'calculatedValue';

    /**
     * @var float
     */
    public $width;

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
     * @param $columnLength
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
     * @see Object_Class_Data::getDataForResource
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        // nothing to do
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        // nothing to do
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\CalculatedValue) {
            $data = Model\DataObject\Service::getCalculatedFieldValueForEditMode($object, [], $data);
        }

        return $data;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     *
     * @param float $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     *
     * @param float $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
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
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        Logger::debug('csv not supported');
        //TODO
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        // nothing to do
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        //TODO
    }

    /**
     * converts data to be imported via webservices
     *
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param $idMapper
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
     * @return null
     */
    public function getColumnType()
    {
        return null;
    }

    public function save()
    {
        // nothing to do
    }

    public function load()
    {
    }

    /**
     * Creates getter code which is used for generation of php file for object classes using this data type
     *
     * @param $class
     *
     * @return string
     */
    public function getGetterCode($class)
    {
        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("object", null, null, null);' . "\n";

        $code .= "\t" . '$data = Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "\t" . "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for localized fields in classes using this data type
     *
     * @param $class
     *
     * @return string
     */
    public function getGetterCodeLocalizedfields($class)
    {
        $key = $this->getName();
        $code  = '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
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

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("localizedfield", "localizedfields", null, $language);' . "\n";

        $code .= "\t" . '$data = Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "\t" . "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for object brick classes using this data type
     *
     * @param $brickClass
     *
     * @return string
     */
    public function getGetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();
        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ($language = null) {' . "\n";

        $code .= "\t" . '$brickDefinition = DataObject\Objectbrick\Definition::getByKey("' . $brickClass->getKey() . '");' . "\n";
        $code .= "\t" . '$fd = $brickDefinition->getFieldDefinition("' . $key . '");' . "\n";

        $code .= "\t" . '$data' . ' = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue($fd->getName());' . "\n";
        $code .= "\t" . '$data->setContextualData("objectbrick", $this->getFieldName() , $this->getType(), $fd->getName(), null, null, $fd);' . "\n";

        $code .= "\t" . '$data = DataObject\Service::getCalculatedFieldValue($this->getObject(), $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "\t" . "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for fieldcollectionk classes using this data type
     *
     * @param $fieldcollectionDefinition
     *
     * @return string
     */
    public function getGetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\DataObject\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("fieldcollection", $this->getFieldname(), $this->getIndex(), null);' . "\n";

        $code .= "\t" . '$data = DataObject\Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for object classes using this data type
     *
     * @param $class
     *
     * @return string
     */
    public function getSetterCode($class)
    {
        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
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
     * @param $brickClass
     *
     * @return string
     */
    public function getSetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($brickClass->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for fieldcollection classes using this data type
     *
     * @param $fieldcollectionDefinition
     *
     * @return string
     */
    public function getSetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($fieldcollectionDefinition->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for localized fields in classes using this data type
     *
     * @param $class
     *
     * @return string
     */
    public function getSetterCodeLocalizedfields($class)
    {
        $key = $this->getName();

        $code  = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($class->getName()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ', $language = null) {' . "\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }
}
