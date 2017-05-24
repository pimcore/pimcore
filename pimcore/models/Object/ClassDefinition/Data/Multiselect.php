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

class Multiselect extends Model\Object\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'multiselect';

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
     * @var int
     */
    public $height;

    /**
     * @var int
     */
    public $maxItems;

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
    public $queryColumnType = 'text';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'text';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'array';

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
     * @param array $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param array $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
    }

    /**
     * @param $maxItems
     *
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
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
        if (is_array($data)) {
            return implode(',', $data);
        }
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
        if (strlen($data)) {
            return explode(',', $data);
        } else {
            return null;
        }
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
        if (!empty($data) && is_array($data)) {
            return ','.implode(',', $data).',';
        }

        return;
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
        if (is_array($data)) {
            return implode(',', $data);
        }
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
        return $data;
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
        if (is_array($data)) {
            return implode(',', $data);
        }
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
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new \Exception('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!is_array($data) and !empty($data)) {
            throw new \Exception('Invalid multiselect data');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Object\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(',', $data);
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return explode(',', $importValue);
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(' ', $data);
        }

        return '';
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  $value
     * @param  $operator
     *
     * @return string
     */
    public function getFilterCondition($value, $operator)
    {
        return $this->getFilterConditionExt($value, $operator, [
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
        if ($operator == '=') {
            $name = $params['name'] ? $params['name'] : $this->name;
            $value = "'%".$value."%'";

            return '`'.$name.'` LIKE '.$value.' ';
        }
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            $map = [];
            foreach ($data as $value) {
                $map[$value] = $value;
            }

            $html = '<ul>';

            foreach ($this->options as $option) {
                if ($map[$option['value']]) {
                    $value = $option['key'];
                    $html .= '<li>' . $value . '</li>';
                }
            }

            $html .= '</ul>';

            $value = [];
            $value['html'] = $html;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->maxItems = $masterDefinition->maxItems;
        $this->options = $masterDefinition->options;
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

    public function enrichFieldDefinition($context = array())
    {
        if ($this->getOptionsProviderClass()) {
            if (method_exists($this->getOptionsProviderClass(), 'getOptions')) {
                $context["fieldname"] = $this->getName();
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
            $context["object"] = $object;
            $context["class"] = $object->getClass();
            $context["fieldname"] = $this->getName();

            if (method_exists($this->getOptionsProviderClass(), 'getOptions')) {
                $options = call_user_func($this->getOptionsProviderClass().'::getOptions', $context, $this);
                $this->setOptions($options);
            }

            return $this;
        }
    }

}
