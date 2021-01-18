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

class Select extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    use DataObject\Traits\DefaultValueTrait;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'select';

    /**
     * Available options to select
     *
     * @var array|null
     */
    public $options;

    /**
     * @var int
     */
    public $width;

    /**
     * @var string|null
     */
    public $defaultValue;

    /** Options provider class
     * @var string
     */
    public $optionsProviderClass;

    /** Options provider data
     * @var string
     */
    public $optionsProviderData;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'varchar';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'varchar';

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
    public $phpdocType = 'string';

    /**
     * @var bool
     */
    public $dynamicOptions = false;

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
     * Correct old column definitions (e.g varchar(255)) to the new format
     *
     * @param string $type
     */
    protected function correctColumnDefinition($type)
    {
        if (preg_match("/(.*)\((\d+)\)/i", $this->$type, $matches)) {
            $this->{'set' . ucfirst($type)}($matches[1]);
            if ($matches[2] > 190) {
                $matches[2] = 190;
            }
            $this->setColumnLength($matches[2] <= 190 ? $matches[2] : 190);
        }
    }

    /**
     * @return string
     */
    public function getColumnType()
    {
        $this->correctColumnDefinition('columnType');

        return $this->columnType . '(' . $this->getColumnLength() . ')';
    }

    /**
     * @return string
     */
    public function getQueryColumnType()
    {
        $this->correctColumnDefinition('queryColumnType');

        return $this->queryColumnType . '(' . $this->getColumnLength() . ')';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

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
     * @param string|int|null $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        return $data;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** See parent class.
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $result = [];

        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = false;
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;

        $value = '';
        foreach ($this->options as $option) {
            if ($option->value == $data) {
                $value = $option->key;
                break;
            }
        }

        $diffdata['value'] = $value;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;

        $result[] = $diffdata;

        return $result;
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
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     * @param string|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return strlen($data) < 1;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Select $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->options = $masterDefinition->options;
        $this->columnLength = $masterDefinition->columnLength;
        $this->defaultValue = $masterDefinition->defaultValue;
        $this->optionsProviderClass = $masterDefinition->optionsProviderClass;
        $this->optionsProviderData = $masterDefinition->optionsProviderData;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string|null $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string
     */
    public function getOptionsProviderClass()
    {
        return $this->optionsProviderClass;
    }

    /**
     * @param string $optionsProviderClass
     */
    public function setOptionsProviderClass($optionsProviderClass)
    {
        $this->optionsProviderClass = $optionsProviderClass;
    }

    /**
     * @return string
     */
    public function getOptionsProviderData()
    {
        return $this->optionsProviderData;
    }

    /**
     * @param string $optionsProviderData
     */
    public function setOptionsProviderData($optionsProviderData)
    {
        $this->optionsProviderData = $optionsProviderData;
    }

    public function enrichFieldDefinition($context = [])
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT
        );

        if ($optionsProvider) {
            $context['fieldname'] = $this->getName();
            $options = $optionsProvider->{'getOptions'}($context, $this);
            $this->setOptions($options);
        }

        return $this;
    }

    /**
     * Override point for Enriching the layout definition before the layout is returned to the admin interface.
     *
     * @param DataObject\Concrete $object
     * @param array $context additional contextual data
     *
     * @return self
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT
        );
        if ($optionsProvider) {
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $this->getName();
            if (!isset($context['purpose'])) {
                $context['purpose'] = 'layout';
            }

            $inheritanceEnabled = DataObject::getGetInheritedValues();
            DataObject::setGetInheritedValues(true);
            $options = $optionsProvider->{'getOptions'}($context, $this);
            DataObject::setGetInheritedValues($inheritanceEnabled);
            $this->setOptions($options);

            $defaultValue = $optionsProvider->{'getDefaultValue'}($context, $this);
            $this->setDefaultValue($defaultValue);

            $hasStaticOptions = $optionsProvider->{'hasStaticOptions'}($context, $this);
            $this->dynamicOptions = !$hasStaticOptions;
        }

        return $this;
    }

    /**
     * @param string|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT
        );

        if ($optionsProvider) {
            $context = $params['context'] ? $params['context'] : [];
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $this->getName();
            $options = $optionsProvider->{'getOptions'}($context, $this);
            $this->setOptions($options);

            if ($params['purpose'] == 'editmode') {
                $result = $data;
            } else {
                $result = ['value' => $data ?? null, 'options' => $this->getOptions()];
            }

            return $result;
        }

        return $data;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param string $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string|null
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $value = is_array($value) ? current($value) : $value;
        $name = $params['name'] ?: $this->name;

        if ($operator === '=') {
            return '`'.$name.'` = '."\"$value\"".' ';
        } elseif ($operator === 'LIKE') {
            return '`'.$name.'` LIKE '."\"%$value%\"".' ';
        }

        return null;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $context
     *
     * @return null|string
     */
    protected function doGetDefaultValue($object, $context = [])
    {
        /** @var DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface $optionsProvider */
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_SELECT
        );
        if ($optionsProvider) {
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $this->getName();
            if (!isset($context['purpose'])) {
                $context['purpose'] = 'layout';
            }

            return $optionsProvider->getDefaultValue($context, $this);
        }

        return $this->getDefaultValue();
    }
}
