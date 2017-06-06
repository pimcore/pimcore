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

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Select extends Model\Object\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'select';

    /**
     * Available options to select
     *
     * @var array
     */
    public $options;

    /**
     * @var int
     */
    public $width;

    /**
     * @var string
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
    public $queryColumnType = 'varchar(190)';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'varchar(190)';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'string';

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
     * @param $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     *
     * @param string $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
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
     * @param null $object
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
     * @param $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return strlen($data) < 1;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->options = $masterDefinition->options;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string $defaultValue
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
        if ($this->getOptionsProviderClass()) {
            if (method_exists($this->getOptionsProviderClass(), 'getOptions')) {
                $context['fieldname'] = $this->getName();
                $options = call_user_func($this->getOptionsProviderClass().'::getOptions', $context, $this);
                $this->setOptions($options);
            }
        }

        return $this;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object Object\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        if ($this->getOptionsProviderClass()) {
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $this->getName();
            if (!isset($context['purpose'])) {
                $context['purpose'] = 'layout';
            }

            if (method_exists($this->getOptionsProviderClass(), 'getOptions')) {
                $options = call_user_func($this->getOptionsProviderClass().'::getOptions', $context, $this);
                $this->setOptions($options);
            }

            if (method_exists($this->getOptionsProviderClass(), 'getDefaultValue')) {
                $defaultValue = call_user_func($this->getOptionsProviderClass().'::getDefaultValue', $context, $this);
                $this->setDefaultValue($defaultValue);
            }

            if (method_exists($this->getOptionsProviderClass(), 'hasStaticOptions')) {
                $hasStaticOptions = call_user_func($this->getOptionsProviderClass().'::hasStaticOptions', $context, $this);
                $this->dynamicOptions = !$hasStaticOptions;
            } else {
                $this->dynamicOptions = true;
            }
        }

        return $this;
    }

    /**
     * @param $data
     * @param null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($this->getOptionsProviderClass()) {
            $context = $params['context'] ? $params['context'] : [];
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $this->getName();

            if (method_exists($this->getOptionsProviderClass(), 'getOptions')) {
                $options = call_user_func($this->getOptionsProviderClass().'::getOptions', $context, $this);
                $this->setOptions($options);
            }

            $result = ['value' => null, 'options' => $this->getOptions()];
            if ($data) {
                $result['value'] = $data;
            }

            return $result;
        } else {
            return $data;
        }
    }
}
