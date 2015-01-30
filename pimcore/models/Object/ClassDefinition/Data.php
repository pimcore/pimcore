<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\Object;

abstract class Data
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $tooltip;

    /**
     * @var boolean
     */
    public $mandatory;

    /**
     * @var boolean
     */
    public $noteditable;

    /**
     * @var integer
     */
    public $index;

    /**
     * @var boolean
     */
    public $locked;

    /**
     * @var boolean
     */
    public $style;

    /**
     * @var array
     */
    public $permissions;

    /**
     * @var string
     */
    public $datatype = "data";

    /**
     * @var string | array
     */
    public $columnType;

    /**
     * @var string | array
     */
    public $queryColumnType;

    /**
     * @var string
     */
    public $fieldtype;

    /**
     * @var bool
     */
    public $relationType = false;

    /**
     * @var bool
     */
    public $invisible = false;

    /**
     * @var bool
     */
    public $visibleGridView = true;

    /**
     * @var bool
     */
    public $visibleSearch = true;

    /** If set to true then null values will not be exported.
     * @var
     */
    protected static $dropNullValues;

    /**
     * @var array
     */
    public static $validFilterOperators = array(
        "LIKE",
        "NOT LIKE",
        "=",
        "IS",
        "IS NOT",
        "!=",
        "<",
        ">",
        ">=",
        "<="
    );

    /**
     * Returns the the data that should be stored in the resource
     *
     * @param mixed $data
     * @return mixed

    abstract public function getDataForResource($data);
     */

    /**
     * Convert the saved data in the resource to the internal eg. Image-Id to Asset\Image object, this is the inverted getDataForResource()
     *
     * @param mixed $data
     * @return mixed

    abstract public function getDataFromResource($data);
     */

    /**
     * Returns the data which should be stored in the query columns
     *
     * @param mixed $data
     * @return mixed

    abstract public function getDataForQueryResource($data);
     */

    /**
     * Returns the data for the editmode
     *
     * @param mixed $data
     * @param null|Object\AbstractObject $object
     * @return mixed
     */
    abstract public function getDataForEditmode($data, $object = null);

    /**
     * Converts data from editmode to internal eg. Image-Id to Asset\Image object
     *
     * @param mixed $data
     * @param null|Object\AbstractObject $object
     * @return mixed
     */
    abstract public function getDataFromEditmode($data, $object = null);

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false) {

        $isEmpty = true;

        // this is to do not treated "0" as empty
        if(is_string($data) || is_numeric($data)) {
            if(strlen($data) > 0) {
                $isEmpty = false;
            }
        }

        if(!empty($data)) {
            $isEmpty = false;
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && $isEmpty) {
            throw new \Exception("Empty mandatory field [ " . $this->getName() . " ]");
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        return $this->getDataFromObjectParam($object);
    }

    /**
     * @param $importValue
     * @return mixed
     */
    public function getFromCsvImport($importValue)
    {
        return $importValue;
    }

    /**
     * converts data to be exposed via webservices
     * @param Object\AbstractObject $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        return $this->getDataFromObjectParam($object);
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null)
    {
        return $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return boolean
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param boolean $mandatory
     * @return void
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool)$mandatory;
        return $this;
    }

    /**
     * @param array $permissions
     * @return void
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setValues($data = array())
    {
        foreach ($data as $key => $value) {
            $method = "set" . $key;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }


    /**
     * @return string
     */
    public function getDatatype()
    {
        return $this->datatype;
    }

    /**
     * @param string $datatype
     * @return void
     */
    public function setDatatype($datatype)
    {
        $this->datatype = $datatype;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldtype()
    {
        return $this->fieldtype;
    }

    /**
     * @param string $fieldtype
     * @return void
     */
    public function setFieldtype($fieldtype)
    {
        $this->fieldtype = $fieldtype;
        return $this;
    }

    /**
     * @return string | array
     */
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * @param string | array $columnType
     * @return void
     */
    public function setColumnType($columnType)
    {
        $this->columnType = $columnType;
        return $this;
    }

    /**
     * @return string | array
     */
    public function getQueryColumnType()
    {
        return $this->queryColumnType;
    }

    /**
     * @param string | array $queryColumnType
     * @return void
     */
    public function setQueryColumnType($queryColumnType)
    {
        $this->queryColumnType = $queryColumnType;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getNoteditable()
    {
        return $this->noteditable;
    }

    /**
     * @param boolean $noteditable
     * @return void
     */
    public function setNoteditable($noteditable)
    {
        $this->noteditable = (bool)$noteditable;
        return $this;
    }

    /**
     * @return integer
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param integer $index
     * @return void
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhpdocType()
    {
        return $this->phpdocType;
    }

    /**
     *
     * @return boolean
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param $style
     * @return $this
     */
    public function setStyle($style)
    {
        $this->style = (string)$style;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @param $locked
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = (bool)$locked;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * @param $tooltip
     * @return $this
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = (string)$tooltip;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isRelationType()
    {
        return $this->relationType;
    }

    /**
     * @return boolean
     */
    public function getInvisible()
    {
        return $this->invisible;
    }

    /**
     * @param $invisible
     * @return $this
     */
    public function setInvisible($invisible)
    {
        $this->invisible = (bool)$invisible;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getVisibleGridView()
    {
        return $this->visibleGridView;
    }

    /**
     * @param $visibleGridView
     * @return $this
     */
    public function setVisibleGridView($visibleGridView)
    {
        $this->visibleGridView = (bool)$visibleGridView;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getVisibleSearch()
    {
        return $this->visibleSearch;
    }

    /**
     * @param $visibleSearch
     * @return $this
     */
    public function setVisibleSearch($visibleSearch)
    {
        $this->visibleSearch = (bool)$visibleSearch;
        return $this;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = array())
    {
        return $tags;
    }

    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies($data)
    {
        return array();
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     * @param  $value
     * @param  $operator
     * @return string
     *
     */
    public function getFilterCondition($value, $operator)
    {
        if ($value === "NULL") {
            if ($operator == '=') {
                $operator = "IS";
            } else if ($operator == "!=") {
                $operator = "IS NOT";
            }
        } else if (!is_array($value) && !is_object($value)) {
            if ($operator == "LIKE") {
                $value = "'%" . $value . "%'";
            } else {
                $value = "'" . $value . "'";
            }
        }

        if (in_array($operator, Object\ClassDefinition\Data::$validFilterOperators)) {
            return "`" . $this->name . "` " . $operator . " " . $value . " ";
        } else return "";
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

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !\Pimcore::inAdmin()) { ' . "\n";
        $code .= "\t\t" . 'return $preValue;' . "\n";
        $code .= "\t" . '}' . "\n";

        if (method_exists($this, "preGetData")) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class->getAllowInherit()) {
            $code .= "\t" . 'if(\Pimcore\Model\Object::doGetInheritedValues() && $this->getClass()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        $code .= "\treturn " . '$data' . ";\n";
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

        if (method_exists($this, "preSetData")) {
            $code .= "\t" . '$this->' . $key . " = " . '$this->getClass()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

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
        $code .= "public function get" . ucfirst($key) . " () {\n";

        if (method_exists($this, "preGetData")) {
            $code .= "\t" . '$data = $this->getDefinition()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t" . 'if(\Pimcore\Model\Object::doGetInheritedValues($this->getObject()) && $this->getDefinition()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
        $code .= "\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
        $code .= "\t" . '}' . "\n";


        $code .= "\t return " . '$data' . ";\n";
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

        if (method_exists($this, "preSetData")) {
            $code .= "\t" . '$this->' . $key . " = " . '$this->getDefinition()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
        $code .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

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

        if (method_exists($this, "preGetData")) {
            $code .= "\t" . '$data = $this->getDefinition()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t return " . '$data' . ";\n";
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

        if (method_exists($this, "preSetData")) {
            $code .= "\t" . '$this->' . $key . " = " . '$this->getDefinition()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

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

        $code .= "\t" . '$data = $this->getLocalizedfields()->getLocalizedValue("' . $key . '", $language);' . "\n";

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !\Pimcore::inAdmin()) { return $preValue;}' . "\n";

        $code .= "\t return " . '$data' . ";\n";
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

        $code .= "\t" . '$this->getLocalizedfields()->setLocalizedValue("' . $key . '", $' . $key . ', $language)' . ";\n";
        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @param $number
     *
     * @return int|null
     */
    public function getAsIntegerCast($number){
        return strlen($number) === 0 ? "" : (int)$number;
    }

    /**
     * @param $number
     * @return float
     */
    public function getAsFloatCast($number){
        return strlen($number) === 0 ? "" : (float)$number;
    }

    /**
     * @param $data
     * @return string
     */
    public function getVersionPreview($data) {
        return "no preview";
    }

    /**
     * @param Object\Concrete $data
     * @return bool
     */
    public function isEmpty($data) {
        if(empty($data)) {
            return true;
        }
        return false;
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return false;
    }

    /** Converts the data sent from the object merger plugin back to the internal object. Similar to
     * getDiffDataForEditMode() an array of data elements is passed in containing the following attributes:
     *  - "field" => the name of (this) field
     *  - "key" => the key of the data element
     *  - "data" => the data
     * @param $data
     * @param null $object
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null) {
        $thedata = $this->getDataFromEditmode($data[0]["data"], $object);
        return $thedata;
    }



    /**
     * Returns the data for the editmode in the format expected by the object merger plugin.
     * The return value is a list of data definitions containing the following attributes:
     *      - "field" => the name of the object field
     *      - "key" => a unique key identifying the data element
     *      - "type" => the type of the data component
     *      - "value" => the value used as preview
     *      - "data" => the actual data which is then sent back again by the editor. Note that the data is opaque
     *                          and will not be touched by the editor in any way.
     *      - "disabled" => whether the data element can be edited or not
     *      - "title" => pretty name describing the data element
     *
     *
     * @param mixed $data
     * @param null|Object\AbstractObject $object
     * @return null|array
     */
    public function getDiffDataForEditMode($data, $object = null) {
        $diffdata = array();
        $diffdata["data"] = $this->getDataForEditmode($data, $object);
        $diffdata["disabled"] = !($this->isDiffChangeAllowed());
        $diffdata["field"] = $this->getName();
        $diffdata["key"] = $this->getName();
        $diffdata["type"] = $this->fieldtype;

        if (method_exists($this, "getDiffVersionPreview")) {
            $value = $this->getDiffVersionPreview($data, $object);
        } else {
            $value = $this->getVersionPreview($data);
        }

        $diffdata["title"] = !empty($this->title) ? $this->title : $this->name;
        $diffdata["value"] = $value;

        $result = array();
        $result[] = $diffdata;
        return $result;
    }

    /**
     * @param  $dropNullValues
     */
    public static function setDropNullValues($dropNullValues)
    {
        self::$dropNullValues = $dropNullValues;
    }

    /**
     * @return
     */
    public static function getDropNullValues()
    {
        return self::$dropNullValues;
    }


    /**
     * @param $object
     * @param array $params
     * @return mixed
     */
    protected function getDataFromObjectParam($object, $params = array()) {

        $data = null;

        $getter = "get".ucfirst($this->getName());
        if(method_exists($object, $getter)) { // for Object\Concrete, Object\Fieldcollection\Data\AbstractData, Object\Objectbrick\Data\AbstractData
            $data = $object->$getter();
        } else if ($object instanceof Object\Localizedfield) {
            $data = $object->getLocalizedValue($this->getName(), $params["language"], true);
        }

        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        // implement in child classes
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function adoptMasterDefinition(Object\ClassDefinition\Data $masterDefinition) {
        $vars = get_object_vars($this);
        $protectedFields = array("noteditable", "invisible");
        foreach ($vars as $name => $value) {
            if (!in_array($name, $protectedFields)) {
                unset($this->$name);
            }
        }
        foreach ($masterDefinition as $name => $value) {
            if (!in_array($name, $protectedFields)) {
                $this->$name = $value;
            }
        }
    }
}
