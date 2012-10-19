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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Object_Class_Data
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
     * Convert the saved data in the resource to the internal eg. Image-Id to Asset_Image object, this is the inverted getDataForResource()
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
     * @param null|Object_Abstract $object
     * @return mixed
     */
    abstract public function getDataForEditmode($data, $object = null);

    /**
     * Converts data from editmode to internal eg. Image-Id to Asset_Image object
     *
     * @param mixed $data
     * @param null|Object_Abstract $object
     * @return mixed
     */
    abstract public function getDataFromEditmode($data, $object = null);

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {

        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Exception("Empty mandatory field [ " . $this->getName() . " ]");
        }

    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        return $object->$getter();
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue)
    {
        return $importValue;
    }

    /**
     * converts data to be exposed via webservices
     * @param Object_Abstract $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        return $object->$getter();
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null)
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
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param boolean $mandatory
     * @return void
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool)$mandatory;
    }

    /**
     * @param array $permissions
     * @return void
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
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
     *
     * @param boolean $style
     */
    public function setStyle($style)
    {
        $this->style = (string)$style;
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
     *
     * @param boolean $locked
     */
    public function setLocked($locked)
    {
        $this->locked = (bool)$locked;
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
     *
     * @param string $tooltip
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = (string)$tooltip;
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
     *
     * @param boolean $invisible
     */
    public function setInvisible($invisible)
    {
        $this->invisible = (bool)$invisible;
    }

    /**
     * @return boolean
     */
    public function getVisibleGridView()
    {
        return $this->visibleGridView;
    }

    /**
     *
     * @param boolean $visibleGridView
     */
    public function setVisibleGridView($visibleGridView)
    {
        $this->visibleGridView = (bool)$visibleGridView;
    }

    /**
     * @return boolean
     */
    public function getVisibleSearch()
    {
        return $this->visibleSearch;
    }

    /**
     *
     * @param boolean $visibleSearch
     */
    public function setVisibleSearch($visibleSearch)
    {
        $this->visibleSearch = (bool)$visibleSearch;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array())
    {
        return $tags;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
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

        if (in_array($operator, Object_Class_Data::$validFilterOperators)) {
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
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}' . "\n";

        if (method_exists($this, "preGetData")) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class->getAllowInherit()) {
            $code .= "\t" . 'if(!$data && Object_Abstract::doGetInheritedValues()) { return $this->getValueFromParent("' . $key . '");}' . "\n";
        }

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
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return void\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";

        if (method_exists($this, "preSetData")) {
            $code .= "\t" . '$this->' . $key . " = " . '$this->getClass()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
        }

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
        $code = '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        $code .= "\t" . 'if(!$this->' . $key . ' && Object_Abstract::doGetInheritedValues($this->getObject())) {' . "\n";
        $code .= "\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
        $code .= "\t" . '}' . "\n";


        $code .= "\t return " . '$this->' . $key . ";\n";
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

        $code = '/**' . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return void\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";
        $code .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
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
        $code = '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . " () {\n";

        $code .= "\t return " . '$this->' . $key . ";\n";
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

        $code = '/**' . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return void\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ") {\n";
        $code .= "\t" . '$this->' . $key . " = " . '$' . $key . ";\n";
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
        $code = '/**' . "\n";
        $code .= '* @return ' . $this->getPhpdocType() . "\n";
        $code .= '*/' . "\n";
        $code .= "public function get" . ucfirst($key) . ' ($language = null) {' . "\n";

        $code .= "\t" . '$data = $this->getLocalizedfields()->getLocalizedValue("' . $key . '", $language);' . "\n";

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}' . "\n";

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

        $code = '/**' . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= "* @return void\n";
        $code .= '*/' . "\n";
        $code .= "public function set" . ucfirst($key) . " (" . '$' . $key . ', $language = null) {' . "\n";

        $code .= "\t" . '$this->getLocalizedfields()->setLocalizedValue("' . $key . '", $' . $key . ', $language)' . ";\n";

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

    public function getAsFloatCast($number){
        return strlen($number) === 0 ? "" : (float)$number;
    }

}
