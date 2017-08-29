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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\DataObject;

abstract class Data
{
    use DataObject\ClassDefinition\Helper\VarExport;

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
     * @var bool
     */
    public $mandatory;

    /**
     * @var bool
     */
    public $noteditable;

    /**
     * @var int
     */
    public $index;

    /**
     * @var bool
     */
    public $locked;

    /**
     * @var bool
     */
    public $style;

    /**
     * @var array
     */
    public $permissions;

    /**
     * @var string
     */
    public $datatype = 'data';

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
    public static $validFilterOperators = [
        'LIKE',
        'NOT LIKE',
        '=',
        'IS',
        'IS NOT',
        '!=',
        '<',
        '>',
        '>=',
        '<='
    ];

    /**
     * Returns the the data that should be stored in the resource
     *
     * @param mixed $data
     *
     * @return mixed

     abstract public function getDataForResource($data);
     */

    /**
     * Convert the saved data in the resource to the internal eg. Image-Id to Asset\Image object, this is the inverted getDataForResource()
     *
     * @param mixed $data
     *
     * @return mixed

     abstract public function getDataFromResource($data);
     */

    /**
     * Returns the data which should be stored in the query columns
     *
     * @param mixed $data
     *
     * @return mixed

     abstract public function getDataForQueryResource($data);
     */

    /**
     * Returns the data for the editmode
     *
     * @param mixed $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    abstract public function getDataForEditmode($data, $object = null, $params = []);

    /**
     * Converts data from editmode to internal eg. Image-Id to Asset\Image object
     *
     * @param mixed $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    abstract public function getDataFromEditmode($data, $object = null, $params = []);

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
        $isEmpty = true;

        // this is to do not treated "0" as empty
        if (is_string($data) || is_numeric($data)) {
            if (strlen($data) > 0) {
                $isEmpty = false;
            }
        }

        if (!empty($data)) {
            $isEmpty = false;
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && $isEmpty) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
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
        return $this->getDataFromObjectParam($object, $params);
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return $importValue;
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        // this is the default, but csv doesn't work for all data types
        return $this->getForCsvExport($object, $params);
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param DataObject\AbstractObject $object
     * @param mixed $params
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
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param $idMapper
     *
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
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
     * @return bool
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
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param bool $mandatory
     *
     * @return $this
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool)$mandatory;

        return $this;
    }

    /**
     * @param array $permissions
     *
     * @return $this
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setValues($data = [])
    {
        foreach ($data as $key => $value) {
            $method = 'set' . $key;
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
     *
     * @return $this
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
     *
     * @return $this
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
     *
     * @return $this
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
     *
     * @return $this
     */
    public function setQueryColumnType($queryColumnType)
    {
        $this->queryColumnType = $queryColumnType;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNoteditable()
    {
        return $this->noteditable;
    }

    /**
     * @param bool $noteditable
     *
     * @return $this
     */
    public function setNoteditable($noteditable)
    {
        $this->noteditable = (bool)$noteditable;

        return $this;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     *
     * @return $this
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
     * @return bool
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param $style
     *
     * @return $this
     */
    public function setStyle($style)
    {
        $this->style = (string)$style;

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * @param $locked
     *
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
     *
     * @return $this
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = (string)$tooltip;

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function isRelationType()
    {
        return $this->relationType;
    }

    /**
     * @return bool
     */
    public function getInvisible()
    {
        return $this->invisible;
    }

    /**
     * @param $invisible
     *
     * @return $this
     */
    public function setInvisible($invisible)
    {
        $this->invisible = (bool)$invisible;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVisibleGridView()
    {
        return $this->visibleGridView;
    }

    /**
     * @param $visibleGridView
     *
     * @return $this
     */
    public function setVisibleGridView($visibleGridView)
    {
        $this->visibleGridView = (bool)$visibleGridView;

        return $this;
    }

    /**
     * @return bool
     */
    public function getVisibleSearch()
    {
        return $this->visibleSearch;
    }

    /**
     * @param $visibleSearch
     *
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
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        return $tags;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        return [];
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  $value
     * @param  $operator
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator)
    {
        return $this->getFilterConditionExt(
            $value,
            $operator,
            [
            'name' => $this->name]
        );
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param $value
     * @param $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $db = \Pimcore\Db::get();
        $name = $params['name'] ? $params['name'] : $this->name;
        $key = $db->quoteIdentifier($name);

        if ($value === 'NULL') {
            if ($operator == '=') {
                $operator = 'IS';
            } elseif ($operator == '!=') {
                $operator = 'IS NOT';
            }
        } elseif (!is_array($value) && !is_object($value)) {
            if ($operator == 'LIKE') {
                $value = $db->quote('%' . $value . '%');
            } else {
                $value = $db->quote($value);
            }
        }

        if (in_array($operator, DataObject\ClassDefinition\Data::$validFilterOperators)) {
            return $key . ' ' . $operator . ' ' . $value . ' ';
        } else {
            return '';
        }
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

        // adds a hook preGetValue which can be defined in an extended class
        $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t" . 'if($preValue !== null && !\Pimcore::inAdmin()) { ' . "\n";
        $code .= "\t\t" . 'return $preValue;' . "\n";
        $code .= "\t" . '}' . "\n";

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
            $code .= "\t" . 'if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        $code .= "\treturn " . '$data' . ";\n";
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
        $returnType = $class instanceof DataObject\Fieldcollection\Definition ? '\\Pimcore\\Model\\DataObject\\FieldCollection\\Data\\' . ucfirst($class->getKey()) :
                    '\\Pimcore\\Model\\DataObject\\' . ucfirst($class->getName());

        $key = $this->getName();
        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= '* @return ' . $returnType . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        if (method_exists($this, 'preSetData')) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$this->getClass()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . ' = ' . '$' . $key . ";\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

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
        $code .= 'public function get' . ucfirst($key) . " () {\n";

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$data = $this->getDefinition()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t" . 'if(\Pimcore\Model\DataObject::doGetInheritedValues($this->getObject()) && $this->getDefinition()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
        $code .= "\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
        $code .= "\t" . '}' . "\n";

        $code .= "\t return " . '$data' . ";\n";
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

        if (method_exists($this, 'preSetData')) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$this->getDefinition()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . ' = ' . '$' . $key . ";\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

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

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$container = $this;' . "\n";
            $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
            $code .= "\t" . '$data = $fd->preGetData($container);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t return " . '$data' . ";\n";
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
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($fieldcollectionDefinition->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ") {\n";

        if (method_exists($this, 'preSetData')) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$this->getDefinition()->getFieldDefinition("' . $key . '")->preSetData($this, $' . $key . ');' . "\n";
        } else {
            $code .= "\t" . '$this->' . $key . ' = ' . '$' . $key . ";\n";
        }

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

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

        $code .= "\t" . '$data = $this->getLocalizedfields()->getLocalizedValue("' . $key . '", $language);' . "\n";

        if (!$class instanceof  DataObject\Fieldcollection\Definition) {
            // adds a hook preGetValue which can be defined in an extended class
            $code .= "\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
            $code .= "\t" . 'if($preValue !== null && !\Pimcore::inAdmin()) { ' . "\n";
            $code .= "\t\t" . 'return $preValue;' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        // we don't need to consider preGetData, because this is already managed directly by the localized fields within getLocalizedValue()

        $code .= "\t return " . '$data' . ";\n";
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
        if ($class instanceof  DataObject\Fieldcollection\Definition) {
            $classname = 'FieldCollection\\Data\\' . ucfirst($class->getKey());
        } else {
            $classname = $class->getName();
        }

        $code  = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($classname) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . '$' . $key . ', $language = null) {' . "\n";

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
    public function getAsIntegerCast($number)
    {
        return strlen($number) === 0 ? '' : (int)$number;
    }

    /**
     * @param $number
     *
     * @return float
     */
    public function getAsFloatCast($number)
    {
        return strlen($number) === 0 ? '' : (float)$number;
    }

    /**
     * @param $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return 'no preview';
    }

    /**
     * @param DataObject\Concrete $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if (empty($data)) {
            return true;
        }

        return false;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    /** Converts the data sent from the object merger plugin back to the internal object. Similar to
     * getDiffDataForEditMode() an array of data elements is passed in containing the following attributes:
     *  - "field" => the name of (this) field
     *  - "key" => the key of the data element
     *  - "data" => the data
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        $thedata = $this->getDataFromEditmode($data[0]['data'], $object, $params);

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
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return null|array
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $diffdata = [];
        $diffdata['data'] = $this->getDataForEditmode($data, $object, $params);
        $diffdata['disabled'] = !($this->isDiffChangeAllowed($object));
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;

        if (method_exists($this, 'getDiffVersionPreview')) {
            $value = $this->getDiffVersionPreview($data, $object, $params);
        } else {
            $value = $this->getVersionPreview($data, $object, $params);
        }

        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;
        $diffdata['value'] = $value;

        $result = [];
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
     * @return bool
     */
    public function getUnique()
    {
        return false;
    }

    /**
     * @param $object
     * @param array $params
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getDataFromObjectParam($object, $params = [])
    {
        $data = null;

        $context = $params && $params['context'] ? $params['context'] : null;

        if ($context) {
            if ($context['containerType'] == 'fieldcollection' || $context['containerType'] == 'block') {
                if ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $object instanceof DataObject\Localizedfield) {
                    $fieldname = $context['fieldname'];
                    $index = $context['index'];

                    if ($object instanceof DataObject\Concrete) {
                        $containerGetter = 'get' . ucfirst($fieldname);
                        $container = $object->$containerGetter();
                        if ($container) {
                            $originalIndex = $context['oIndex'];

                            // field collection or block items
                            if (!is_null($originalIndex)) {
                                if ($context['containerType'] == 'block') {
                                    $items = $container;
                                } else {
                                    $items = $container->getItems();
                                }

                                if ($items && count($items) > $originalIndex) {
                                    $item = $items[$originalIndex];

                                    if ($context['containerType'] == 'block') {
                                        $data = $item[$this->getName()];
                                        if ($data instanceof  DataObject\Data\BlockElement) {
                                            $data = $data->getData();

                                            return $data;
                                        }
                                    } else {
                                        $getter = 'get' . ucfirst($this->getName());
                                        $data = $item->$getter();

                                        if ($object instanceof DataObject\Localizedfield) {
                                            $data = $data->getLocalizedValue($this->getName(), $params['language'], true);
                                        }
                                    }

                                    return $data;
                                } else {
                                    throw new \Exception('object seems to be modified, item with orginal index ' . $originalIndex . ' not found, new index: ' . $index);
                                }
                            } else {
                                return null;
                            }
                        } else {
                            return null;
                        }
                    } elseif ($object instanceof DataObject\Localizedfield) {
                        $data = $object->getLocalizedValue($this->getName(), $params['language'], true);

                        return $data;
                    }
                }
            } elseif ($context['containerType'] == 'classificationstore') {
                $fieldname = $context['fieldname'];
                $getter = 'get' . ucfirst($fieldname);
                if (method_exists($object, $getter)) {
                    $groupId = $context['groupId'];
                    $keyId = $context['keyId'];
                    $language = $context['language'];

                    /** @var $classificationStoreData DataObject\Classificationstore */
                    $classificationStoreData = $object->$getter();
                    $data = $classificationStoreData->getLocalizedKeyValue($groupId, $keyId, $language, true, true);

                    return $data;
                }
            }
        }

        $container = $object;

        $getter = 'get' . ucfirst($this->getName());
        if (method_exists($container, $getter)) { // for DataObject\Concrete, DataObject\Fieldcollection\Data\AbstractData, DataObject\Objectbrick\Data\AbstractData
            $data = $container->$getter();
        } elseif ($object instanceof DataObject\Localizedfield) {
            $data = $object->getLocalizedValue($this->getName(), $params['language'], true);
        }

        return $data;
    }

    /**
     * @param DataObject\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        // implement in child classes
    }

    /**
     * @param DataObject\ClassDefinition\Data $masterDefinition
     */
    public function adoptMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $vars = get_object_vars($this);
        $protectedFields = ['noteditable', 'invisible'];
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

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($params['raw']) {
            return $value;
        } else {
            return ['value' => $value];
        }
    }

    /** See marshal
     * @param mixed $data
     * @param Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return mixed
     */
    public function unmarshal($data, $object = null, $params = [])
    {
        if ($params['raw']) {
            return $data;
        } else {
            if (is_array($data)) {
                return $data['value'];
            }
        }

        return null;
    }
}
