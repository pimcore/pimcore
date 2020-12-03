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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;

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
     * @deprecated implement getPhpdocInputType() and getPhpdocReturnType() instead
     *
     * @var string
     */
    public $phpdocType;

    /**
     * @var bool
     */
    public $locked;

    /**
     * @var string
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
     * If set to true then null values will not be exported.
     *
     * @var bool
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
        '<=',
    ];

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
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return $importValue;
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
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
     * @deprecated
     *
     * @param DataObject\Concrete $object
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
     * @deprecated
     *
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
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
     * @param array $blockedKeys
     *
     * @return $this
     */
    public function setValues($data = [], $blockedKeys = [])
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
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
     * @deprecated use getPhpdocInputType() and getPhpdocReturnType() instead
     *
     * @return string
     */
    public function getPhpdocType()
    {
        return $this->phpdocType;
    }

    /**
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param string|null $style
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
     * @param int|bool|null $locked
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
     * @param string|null $tooltip
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
     * @param bool|int|null $invisible
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
     * @param bool|int|null $visibleGridView
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
     * @param bool|int|null $visibleSearch
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
     * @param mixed $data
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
     * @param  mixed $value
     * @param  string $operator
     * @param  mixed $params
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator, $params = [])
    {
        $params['name'] = $this->name;

        return $this->getFilterConditionExt(
            $value,
            $operator,
            $params
        );
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param string|array $value
     * @param string $operator
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
     * @param string $key
     *
     * @return string
     */
    protected function getPreGetValueHookCode(string $key): string
    {
        $code = "\t" . 'if($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {' . " \n";
        $code .= "\t\t" . '$preValue = $this->preGetValue("' . $key . '");' . " \n";
        $code .= "\t\t" . 'if($preValue !== null) { ' . "\n";
        $code .= "\t\t\t" . 'return $preValue;' . "\n";
        $code .= "\t\t" . '}' . "\n";
        $code .= "\t" . '}' . " \n\n";

        return $code;
    }

    /**
     * @return string|null
     */
    public function getParameterTypeDeclaration(): ?string
    {
        if ($this->getPhpdocInputType()) {
            return '?' . $this->getPhpdocInputType();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getReturnTypeDeclaration(): ?string
    {
        if ($this->getPhpdocReturnType()) {
            return '?' . $this->getPhpdocReturnType();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getPhpdocInputType(): ?string
    {
        if ($this->getPhpdocType()) {
            return $this->getPhpdocType();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getPhpdocReturnType(): ?string
    {
        if ($this->getPhpdocType()) {
            return $this->getPhpdocType();
        }

        return null;
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

        if ($class->getGenerateTypeDeclarations() && $this->getReturnTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '';

        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ()' . $typeDeclaration . " {\n";

        $code .= $this->getPreGetValueHookCode($key);

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$data = $this->getClass()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n\n";
        }

        // insert this line if inheritance from parent objects is allowed
        if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit() && $this->supportsInheritance()) {
            $code .= "\t" . 'if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'try {' . "\n";
            $code .= "\t\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
            $code .= "\t\t\t" . '// no data from parent available, continue ... ' . "\n";
            $code .= "\t\t" . '}' . "\n";
            $code .= "\t" . '}' . "\n\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . '    return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n\n";

        $code .= "\treturn " . '$data' . ";\n";
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
        if ($class instanceof DataObject\Objectbrick\Definition) {
            $classname = 'Objectbrick\\Data\\' . ucfirst($class->getKey());
        } elseif ($class instanceof DataObject\Fieldcollection\Definition) {
            $classname = 'Fieldcollection\\Data\\' . ucfirst($class->getKey());
        } else {
            $classname = $class->getName();
        }

        $key = $this->getName();

        if ($class->getGenerateTypeDeclarations() && $this->getParameterTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($classname) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . $typeDeclaration . '$' . $key . ") {\n";
        $code .= "\t" . '$fd = $this->getClass()->getFieldDefinition("' . $key . '");' . "\n";

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '$encryptedFd = $this->getClass()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
                $code .= "\t" . '$inheritValues = self::getGetInheritedValues();'."\n";
                $code .= "\t" . 'self::setGetInheritedValues(false);'."\n";
            }

            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";
            $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
                $code .= "\t" . 'self::setGetInheritedValues($inheritValues);'."\n";
            }
            $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
            $code .= "\t" . 'if (!$isEqual) {' . "\n";
            $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        if (method_exists($this, 'preSetData')) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$fd->preSetData($this, $' . $key . ');' . "\n";
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
     * @param DataObject\Objectbrick\Definition $brickClass
     *
     * @return string
     */
    public function getGetterCodeObjectbrick($brickClass)
    {
        $key = $this->getName();

        if ($brickClass->getGenerateTypeDeclarations() && $this->getReturnTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ()' . $typeDeclaration . " {\n";

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$data = $this->getDefinition()->getFieldDefinition("' . $key . '")->preGetData($this);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        if ($this->supportsInheritance()) {
            $code .= "\t" . 'if(\Pimcore\Model\DataObject::doGetInheritedValues($this->getObject()) && $this->getDefinition()->getFieldDefinition("' . $key . '")->isEmpty($data)) {' . "\n";
            $code .= "\t\t" . 'try {' . "\n";
            $code .= "\t\t\t" . 'return $this->getValueFromParent("' . $key . '");' . "\n";
            $code .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
            $code .= "\t\t\t" . '// no data from parent available, continue ... ' . "\n";
            $code .= "\t\t" . '}' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n";

        $code .= "\t return " . '$data' . ";\n";
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

        if ($brickClass->getGenerateTypeDeclarations() && $this->getParameterTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickClass->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . $typeDeclaration . '$' . $key . ") {\n";
        $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '$encryptedFd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$class = $this->getObject() ? $this->getObject()->getClass() : null;' . "\n";
            $code .= "\t" . 'if($class && $class->getAllowInherit()) {' . "\n";
            $code .= "\t\t" . '$inheritValues = $this->getObject()::getGetInheritedValues();'."\n";
            $code .= "\t\t" . '$this->getObject()::setGetInheritedValues(false);'."\n";
            $code .= "\t" . '}'."\n";

            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";
            $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            $code .= "\t" . 'if($class && $class->getAllowInherit()) {' . "\n";
            $code .= "\t\t" . '$this->getObject()::setGetInheritedValues($inheritValues);'."\n";
            $code .= "\t" . '}' . "\n";
            $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
            $code .= "\t" . 'if (!$isEqual) {' . "\n";
            $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        if (method_exists($this, 'preSetData')) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$fd->preSetData($this, $' . $key . ');' . "\n";
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
     * @param DataObject\Fieldcollection\Definition $fieldcollectionDefinition
     *
     * @return string
     */
    public function getGetterCodeFieldcollection($fieldcollectionDefinition)
    {
        $key = $this->getName();

        if ($fieldcollectionDefinition->getGenerateTypeDeclarations() && $this->getReturnTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ()' . $typeDeclaration . " {\n";

        if (method_exists($this, 'preGetData')) {
            $code .= "\t" . '$container = $this;' . "\n";
            $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
            $code .= "\t" . '$data = $fd->preGetData($container);' . "\n";
        } else {
            $code .= "\t" . '$data = $this->' . $key . ";\n";
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . '    return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n";

        $code .= "\t return " . '$data' . ";\n";
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

        if ($fieldcollectionDefinition->getGenerateTypeDeclarations() && $this->getParameterTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '';
        $code .= '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($fieldcollectionDefinition->getKey()) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . $typeDeclaration . '$' . $key . ") {\n";
        $code .= "\t" . '$fd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '$encryptedFd = $this->getDefinition()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";
            $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";
            $code .= "\t" . 'if (!$isEqual) {' . "\n";
            $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            $code .= "\t" . '}' . "\n";
        }

        if (method_exists($this, 'preSetData')) {
            $code .= "\t" . '$this->' . $key . ' = ' . '$fd->preSetData($this, $' . $key . ');' . "\n";
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
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getGetterCodeLocalizedfields($class)
    {
        $key = $this->getName();

        if ($class->getGenerateTypeDeclarations() && $this->getReturnTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = ': ' . $this->getReturnTypeDeclaration();
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Get ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @return ' . $this->getPhpdocReturnType() . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function get' . ucfirst($key) . ' ($language = null)' . $typeDeclaration . ' {' . "\n";

        $code .= "\t" . '$data = $this->getLocalizedfields()->getLocalizedValue("' . $key . '", $language);' . "\n";

        if (!$class instanceof DataObject\Fieldcollection\Definition && !$class instanceof DataObject\Objectbrick\Definition) {
            $code .= $this->getPreGetValueHookCode($key);
        }

        $code .= "\t" . 'if ($data instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField) {' . "\n";
        $code .= "\t\t" . 'return $data->getPlain();' . "\n";
        $code .= "\t" . '}' . "\n";

        // we don't need to consider preGetData, because this is already managed directly by the localized fields within getLocalizedValue()

        $code .= "\treturn " . '$data' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates setter code which is used for generation of php file for localized fields in classes using this data type
     *
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getSetterCodeLocalizedfields($class)
    {
        $key = $this->getName();
        if ($class instanceof DataObject\Objectbrick\Definition) {
            $classname = 'Objectbrick\\Data\\' . ucfirst($class->getKey());
            $containerGetter = 'getDefinition';
        } elseif ($class instanceof DataObject\Fieldcollection\Definition) {
            $classname = 'Fieldcollection\\Data\\' . ucfirst($class->getKey());
            $containerGetter = 'getDefinition';
        } else {
            $classname = $class->getName();
            $containerGetter = 'getClass';
        }

        if ($class->getGenerateTypeDeclarations() && $this->getParameterTypeDeclaration() && $this instanceof DataObject\ClassDefinition\Data\TypeDeclarationSupportInterface) {
            $typeDeclaration = $this->getParameterTypeDeclaration() . ' ';
        } else {
            $typeDeclaration = '';
        }

        $code = '/**' . "\n";
        $code .= '* Set ' . str_replace(['/**', '*/', '//'], '', $this->getName()) . ' - ' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . "\n";
        $code .= '* @param ' . $this->getPhpdocInputType() . ' $' . $key . "\n";
        $code .= '* @return \\Pimcore\\Model\\DataObject\\' . ucfirst($classname) . "\n";
        $code .= '*/' . "\n";
        $code .= 'public function set' . ucfirst($key) . ' (' . $typeDeclaration . '$' . $key . ', $language = null) {' . "\n";
        if ($this->supportsDirtyDetection()) {
            $code .= "\t" . '$fd = $this->' . $containerGetter . '()->getFieldDefinition("localizedfields")->getFieldDefinition("' . $key . '");' . "\n";
        }

        if ($this instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            if ($this->getDelegate()) {
                $code .= "\t" . '$encryptedFd = $this->getClass()->getFieldDefinition("' . $key . '");' . "\n";
                $code .= "\t" . '$delegate = $encryptedFd->getDelegate();' . "\n";
                $code .= "\t" . 'if ($delegate && !($' . $key . ' instanceof \\Pimcore\\Model\\DataObject\\Data\\EncryptedField)) {' . "\n";
                $code .= "\t\t" . '$' . $key . ' = new \\Pimcore\\Model\\DataObject\\Data\\EncryptedField($delegate, $' . $key . ');' . "\n";
                $code .= "\t" . '}' . "\n";
            }
        }

        if ($this->supportsDirtyDetection()) {
            if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
                $code .= "\t" . '$inheritValues = self::getGetInheritedValues();'."\n";
                $code .= "\t" . 'self::setGetInheritedValues(false);'."\n";
            }

            $code .= "\t" . '$hideUnpublished = \\Pimcore\\Model\\DataObject\\Concrete::getHideUnpublished();' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished(false);' . "\n";
            $code .= "\t" . '$currentData = $this->get' . ucfirst($this->getName()) . '($language);' . "\n";
            $code .= "\t" . '\\Pimcore\\Model\\DataObject\\Concrete::setHideUnpublished($hideUnpublished);' . "\n";

            if ($class instanceof DataObject\ClassDefinition && $class->getAllowInherit()) {
                $code .= "\t" . 'self::setGetInheritedValues($inheritValues);'."\n";
            }
            $code .= "\t" . '$isEqual = $fd->isEqual($currentData, $' . $key . ');' . "\n";

            $code .= "\t" . 'if (!$isEqual) {' . "\n";
            $code .= "\t\t" . '$this->markFieldDirty("' . $key . '", true);' . "\n";
            $code .= "\t" . '}' . "\n";
        } else {
            $code .= "\t" . '$isEqual = false;' . "\n";
        }

        $code .= "\t" . '$this->getLocalizedfields()->setLocalizedValue("' . $key . '", $' . $key . ', $language, !$isEqual)' . ";\n";

        $code .= "\t" . 'return $this;' . "\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * Creates filter method code for listing classes
     *
     * @return string
     */
    public function getFilterCode()
    {
        $key = $this->getName();

        $code = '/**' . "\n";
        $code .= '* Filter by ' . str_replace(['/**', '*/', '//'], '', $key) . ' (' . str_replace(['/**', '*/', '//'], '', $this->getTitle()) . ")\n";

        $dataParamDoc = 'mixed $data';
        $reflectionMethod = new \ReflectionMethod($this, 'addListingFilter');
        if (preg_match('/@param\s+([^\s]+)\s+\$data(.*)/', $reflectionMethod->getDocComment(), $dataParam)) {
            $dataParamDoc = $dataParam[1].' $data '.$dataParam[2];
        }

        $operatorParamDoc = 'string $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"';
        if (preg_match('/@param\s+([^\s]+)\s+\$operator(.*)/', $reflectionMethod->getDocComment(), $dataParam)) {
            $operatorParamDoc = $dataParam[1].' $operator '.$dataParam[2];
        }

        $code .= '* @param '.$dataParamDoc."\n";
        $code .= '* @param '.$operatorParamDoc."\n";
        $code .= '* @return static'."\n";
        $code .= '*/' . "\n";

        $code .= 'public function filterBy' . ucfirst($key) .' ($data, $operator = \'=\') {'."\n";
        $code .= "\t" . '$this->getClass()->getFieldDefinition("' . $key . '")->addListingFilter($this, $data, $operator);' . "\n";
        $code .= "\treturn " . '$this' . ";\n";
        $code .= "}\n\n";

        return $code;
    }

    /**
     * @param int|string|null $number
     *
     * @return int|null
     */
    public function getAsIntegerCast($number)
    {
        return strlen($number) === 0 ? '' : (int)$number;
    }

    /**
     * @param mixed $number
     *
     * @return float
     */
    public function getAsFloatCast($number)
    {
        return strlen($number) === 0 ? '' : (float)$number;
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return 'no preview';
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return empty($data);
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
    }

    /** Converts the data sent from the object merger back to the internal object. Similar to
     * getDiffDataForEditMode() an array of data elements is passed in containing the following attributes:
     *  - "field" => the name of (this) field
     *  - "key" => the key of the data element
     *  - "data" => the data
     *
     * @param array $data
     * @param DataObject\Concrete|null $object
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
     * @param bool $dropNullValues
     */
    public static function setDropNullValues($dropNullValues)
    {
        self::$dropNullValues = $dropNullValues;
    }

    /**
     * @return bool
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
     *  @internal
     *
     * @param mixed $data
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     *
     * @throws \Exception
     */
    protected function setDataToObject($data, $container, $params = [])
    {
        $context = $params['context'] ?? null;

        if (isset($context['containerType'])) {
            if ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'block') {
                if ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $container instanceof DataObject\Localizedfield) {
                    $fieldname = $context['fieldname'];

                    if ($container instanceof DataObject\Concrete) {
                        $containerGetter = 'get' . ucfirst($fieldname);
                        $parentContainer = $container->$containerGetter();

                        if ($parentContainer) {
                            $originalIndex = $context['oIndex'] ?? null;

                            // field collection or block items
                            if ($originalIndex !== null) {
                                if ($context['containerType'] === 'block') {
                                    $items = $parentContainer;
                                } else {
                                    $items = $parentContainer->getItems();
                                }

                                if ($items && count($items) > $originalIndex) {
                                    $item = $items[$originalIndex];

                                    if ($context['containerType'] === 'block') {
                                        $data = $item[$this->getName()] ?? null;
                                        if ($data instanceof DataObject\Data\BlockElement) {
                                            $data->setData($data);
                                        }
                                    } else {
                                        if ($container instanceof DataObject\Localizedfield) {
                                            $item->setLocalizedValue($this->getName(), $data, $params['language']);
                                        } else {
                                            $setter = 'set' . ucfirst($this->getName());
                                            $item->$setter($data);
                                        }
                                    }
                                }
                            }
                        }
                    } elseif ($container instanceof DataObject\Localizedfield) {
                        $container->setLocalizedValue($this->getName(), $data, $params['language']);
                    }
                }
            } elseif ($context['containerType'] === 'objectbrick' && ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $container instanceof DataObject\Localizedfield)) {
                $fieldname = $context['fieldname'];

                if ($container instanceof DataObject\Concrete) {
                    $containerGetter = 'get' . ucfirst($fieldname);
                    $parentContainer = $container->$containerGetter();
                    if ($parentContainer) {
                        $brickGetter = 'get' . ucfirst($context['containerKey']);
                        $brickData = $parentContainer->$brickGetter();

                        if ($brickData instanceof DataObject\Objectbrick\Data\AbstractData) {
                            $brickData->set('localizedfields', $data);
                        }
                    }
                } elseif ($container instanceof DataObject\Localizedfield) {
                    $container->setLocalizedValue($this->getName(), $data, $params['language']);
                }
            } elseif ($context['containerType'] === 'classificationstore') {
                $fieldname = $context['fieldname'];
                $getter = 'get' . ucfirst($fieldname);
                if (method_exists($container, $getter)) {
                    $groupId = $context['groupId'];
                    $keyId = $context['keyId'];
                    $language = $context['language'];

                    /** @var DataObject\Classificationstore $classificationStoreData */
                    $classificationStoreData = $container->$getter();
                    $classificationStoreData->setLocalizedKeyValue($groupId, $keyId, $data, $language);
                }
            }
        }

        $setter = 'set' . ucfirst($this->getName());
        if (method_exists($container, $setter)) { // for DataObject\Concrete, DataObject\Fieldcollection\Data\AbstractData, DataObject\Objectbrick\Data\AbstractData
            $container->$setter($data);
        } elseif ($container instanceof DataObject\Localizedfield) {
            $container->setLocalizedValue($this->getName(), $data, $params['language']);
        }
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getDataFromObjectParam($object, $params = [])
    {
        $data = null;

        if (array_key_exists('injectedData', $params)) {
            return $params['injectedData'];
        }

        $context = $params['context'] ?? null;

        if (isset($context['containerType'])) {
            if ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'block') {
                if ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $object instanceof DataObject\Localizedfield) {
                    $fieldname = $context['fieldname'];
                    $index = $context['index'] ?? null;

                    if ($object instanceof DataObject\Concrete) {
                        $containerGetter = 'get' . ucfirst($fieldname);
                        $container = $object->$containerGetter();
                        if (!$container && $context['containerType'] === 'block') {
                            // no data, so check if inheritance is enabled + there is parent value
                            if ($object->getClass()->getAllowInherit()) {
                                try {
                                    $container = $object->getValueFromParent($fieldname);
                                } catch (InheritanceParentNotFoundException $e) {
                                    //nothing to do here - just no parent data available
                                }
                            }
                        }

                        if ($container) {
                            $originalIndex = $context['oIndex'] ?? null;

                            // field collection or block items
                            if ($originalIndex !== null) {
                                if ($context['containerType'] === 'block') {
                                    $items = $container;
                                } else {
                                    $items = $container->getItems();
                                }

                                if ($items && count($items) > $originalIndex) {
                                    $item = $items[$originalIndex];

                                    if ($context['containerType'] === 'block') {
                                        $data = $item[$this->getName()] ?? null;
                                        if ($data instanceof DataObject\Data\BlockElement) {
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
            } elseif ($context['containerType'] === 'objectbrick' && ($this instanceof DataObject\ClassDefinition\Data\Localizedfields || $object instanceof DataObject\Localizedfield)) {
                $fieldname = $context['fieldname'];

                if ($object instanceof DataObject\Concrete) {
                    $containerGetter = 'get' . ucfirst($fieldname);
                    $container = $object->$containerGetter();
                    if ($container) {
                        $brickGetter = 'get' . ucfirst($context['containerKey']);
                        $brickData = $container->$brickGetter();

                        if ($brickData instanceof DataObject\Objectbrick\Data\AbstractData) {
                            return $brickData->get('localizedfields');
                        }
                    }

                    return null;
                } elseif ($object instanceof DataObject\Localizedfield) {
                    $data = $object->getLocalizedValue($this->getName(), $params['language'], true);

                    return $data;
                }
            } elseif ($context['containerType'] === 'classificationstore') {
                $fieldname = $context['fieldname'];
                $getter = 'get' . ucfirst($fieldname);
                if (method_exists($object, $getter)) {
                    $groupId = $context['groupId'];
                    $keyId = $context['keyId'];
                    $language = $context['language'];

                    /** @var DataObject\Classificationstore $classificationStoreData */
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
        $vars = get_object_vars($masterDefinition);
        foreach ($vars as $name => $value) {
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
        if (isset($params['raw']) && $params['raw']) {
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
        if (isset($params['raw']) && $params['raw']) {
            return $data;
        } else {
            if (is_array($data)) {
                return $data['value'];
            }
        }

        return null;
    }

    /**
     * @param array|null $existingData
     * @param array $additionalData
     *
     * @return mixed
     */
    public function appendData($existingData, $additionalData)
    {
        return $existingData;
    }

    /**
     * @param mixed $existingData
     * @param mixed $removeData
     *
     * @return mixed
     */
    public function removeData($existingData, $removeData)
    {
        return $existingData;
    }

    /**
     * Returns if datatype supports data inheritance
     *
     * @return bool
     */
    public function supportsInheritance()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportsDirtyDetection()
    {
        return false;
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue)
    {
        @trigger_error('Calling '.__METHOD__.' is deprecated since version 6.7.0 and will be removed in 7.0.0. ' .
            'Implement `' . DataObject\ClassDefinition\Data\EqualComparisonInterface::class . '` instead.', E_USER_DEPRECATED);

        return false;
    }

    /**
     * @param DataObject\Concrete $object
     */
    public function markLazyloadedFieldAsLoaded($object)
    {
        if ($object instanceof DataObject\LazyLoadedFieldsInterface) {
            $object->markLazyKeyAsLoaded($this->getName());
        }
    }

    /**
     * Returns if datatype supports listing filters: getBy, filterBy
     *
     * @return bool
     */
    public function isFilterable(): bool
    {
        return false;
    }

    /**
     * @param DataObject\Listing            $listing
     * @param string|int|float|float|array $data comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string                        $operator SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return DataObject\Listing
     */
    public function addListingFilter(DataObject\Listing $listing, $data, $operator = '=')
    {
        return $listing->addFilterByField($this->getName(), $operator, $data);
    }
}
