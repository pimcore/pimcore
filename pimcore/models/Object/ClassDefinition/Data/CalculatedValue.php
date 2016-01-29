<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class CalculatedValue extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "calculatedValue";

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
    public $queryColumnType = "varchar";

    /**
     * Column length
     *
     * @var integer
     */
    public $columnLength = 255;

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\CalculatedValue";

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return void
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
     * @param float $data
     * @return float
     */
    public function getDataForResource($data, $object = null)
    {
        // nothing to do
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param float $data
     * @return float
     */
    public function getDataFromResource($data)
    {
        // nothing to do
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param float $data
     * @return float
     */
    public function getDataForQueryResource($data, $object = null)
    {
        return $data;
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param float $data
     * @return float
     */
    public function getDataForEditmode($data, $object = null)
    {
        if ($data instanceof Model\Object\Data\CalculatedValue) {
            $data = Model\Object\Service::getCalculatedFieldValueForEditMode($object, $data);
        }
        return $data;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param float $data
     * @return float
     */
    public function getDataFromEditmode($data, $object = null)
    {
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param float $data
     * @return float
     */
    public function getVersionPreview($data)
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        // nothing to do
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        \Logger::debug("csv not supported");
        //TODO
    }


    /**
     * fills object field data values from CSV Import String
     * @param string $importValue
     * @return double
     */
    public function getFromCsvImport($importValue)
    {
        // nothing to do
    }


       /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        //TODO
    }

     /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value)
    {
        // nothing to do
    }

    /**
     * @return string
     */
    public function getQueryColumnType()
    {
        return $this->queryColumnType . "(" . $this->getColumnLength() . ")";
    }

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
     * @param $class
     * @return string
     */
    public function getGetterCode($class)
    {
        $key = $this->getName();
        $code = "";

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\Object\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("object", null, null, null);' . "\n";

        $code .= "\t" . '$data = Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "\t" . "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for localized fields in classes using this data type
     * @param $class
     * @return string
     */
    public function getGetterCodeLocalizedfields($class)
    {
        $key = $this->getName();
        $code  = '/**' . "\n";
        $code .= '* Get ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . ' ($language = null) {' . "\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\Object\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("localizedfield", "localizedfields", null, $language);' . "\n";

        $code .= "\t" . '$data = Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "\t" . "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for object brick classes using this data type
     * @param $brickClass
     * @return string
     */
    public function getGetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();
        $code = "";
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . ' ($language = null) {' . "\n";


        $code .= "\t" . '$brickDefinition = Object\Objectbrick\Definition::getByKey("' . $brickClass->getKey() . '");' . "\n";
        $code .= "\t" . '$fd = $brickDefinition->getFieldDefinition("' . $key . '");' . "\n";

        $code .= "\t" . '$data' . ' = new \\Pimcore\\Model\\Object\\Data\\CalculatedValue($fd->getName());' . "\n";
        $code .= "\t" . '$data->setContextualData("objectbrick", $this->getFieldName() , $this->getType(), $fd->getName(), null, null, $fd);' . "\n";

        $code .= "\t" . '$data = Object\Service::getCalculatedFieldValue($this->getObject(), $data);' . "\n";
        $code .= "\treturn " . '$data' . ";\n";
        $code .= "\t" . "}\n\n";

        return $code;
    }

    /**
     * Creates getter code which is used for generation of php file for fieldcollectionk classes using this data type
     * @param $fieldcollectionDefinition
     * @return string
     */
    public function getGetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();
        $code = "";

        $code = "";
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        $code .= "\t" . '$data' . " = new \\Pimcore\\Model\\Object\\Data\\CalculatedValue('" . $key . "');\n";
        $code .= "\t" . '$data->setContextualData("fieldcollection", $this->getFieldname(), $this->getIndex(), null);' . "\n";

        $code .= "\t" . '$data = Object\Service::getCalculatedFieldValue($this, $data);' . "\n";
        $code .= "\t return " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for object classes using this data type
     * @param $class
     * @return string
     */
    public function getSetterCode($class)
    {
        $key = $this->getName();
        $code = "";

        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return \\Pimcore\\Model\\Object\\" . ucfirst($class->getName()) . "\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }


    /**
     * Creates setter code which is used for generation of php file for object brick classes using this data type
     * @param $brickClass
     * @return string
     */
    public function getSetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();

        $code = "";
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return \\Pimcore\\Model\\Object\\" . ucfirst($brickClass->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";


        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for fieldcollection classes using this data type
     * @param $fieldcollectionDefinition
     * @return string
     */
    public function getSetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();
        $code = "";

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return \\Pimcore\\Model\\Object\\" . ucfirst($fieldcollectionDefinition->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for localized fields in classes using this data type
     * @param $class
     * @return string
     */
    public function getSetterCodeLocalizedfields($class)
    {
        $key = $this->getName();

        $code  = '/**' . "\n";
        $code .= '* Set ' . str_replace(array("/**", "*/", "//"), "", $this->getName()) . " - " . str_replace(array("/**", "*/", "//"), "", $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return \\Pimcore\\Model\\Object\\" . ucfirst($class->getName()) . "\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ', $language = null) {' . "\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }
}
