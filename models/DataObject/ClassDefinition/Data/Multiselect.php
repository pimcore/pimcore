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

class Multiselect extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

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

    /**
     * @var string
     */
    public $renderType;

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
     * @var bool
     */
    public $dynamicOptions = false;

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
     * @param int|string|null $maxItems
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
     * @param string|null $renderType
     *
     * @return $this
     */
    public function setRenderType($renderType)
    {
        $this->renderType = $renderType;

        return $this;
    }

    /**
     * @return string
     */
    public function getRenderType()
    {
        return $this->renderType;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            return implode(',', $data);
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (strlen($data)) {
            return explode(',', $data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if (!empty($data) && is_array($data)) {
            return ','.implode(',', $data).',';
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            return implode(',', $data);
        }

        return null;
    }

    /**
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
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
        return $data;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            return implode(',', $data);
        }

        return null;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws Model\Element\ValidationException
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!is_array($data) and !empty($data)) {
            throw new Model\Element\ValidationException('Invalid multiselect data');
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(',', $data);
        }

        return '';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return explode(',', $importValue);
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
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
     * @param  string $value
     * @param  string $operator
     * @param  array $params
     *
     * @return string
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
     * @param string $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string|null
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        if ($operator == '=') {
            $name = $params['name'] ? $params['name'] : $this->name;
            $value = "'%,".$value.",%'";

            return '`'.$name.'` LIKE '.$value.' ';
        }

        return null;
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param array|null $data
     * @param DataObject\Concrete|null $object
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
     * @param DataObject\ClassDefinition\Data\Multiselect $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
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

    public function enrichFieldDefinition($context = [])
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
                DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );
        if ($optionsProvider) {
            $context['fieldname'] = $this->getName();

            $options = $optionsProvider->{'getOptions'}($context, $this);
            $this->setOptions($options);
        }

        return $this;
    }

    /**
     * Override point for enriching the layout definition before the layout is returned to the admin interface.
     *
     * @param DataObject\Concrete $object
     * @param array $context additional contextual data
     *
     * @return $this
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );

        if ($optionsProvider) {
            $context['object'] = $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }
            $context['fieldname'] = $this->getName();

            $inheritanceEnabled = DataObject::getGetInheritedValues();
            DataObject::setGetInheritedValues(true);
            $options = $optionsProvider->{'getOptions'}($context, $this);
            DataObject::setGetInheritedValues($inheritanceEnabled);
            $this->setOptions($options);

            $hasStaticOptions = $optionsProvider->{'hasStaticOptions'}($context, $this);
            $this->dynamicOptions = !$hasStaticOptions;
        }

        return $this;
    }

    /**
     * @param array|null $existingData
     * @param array $additionalData
     *
     * @return mixed
     */
    public function appendData($existingData, $additionalData)
    {
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $existingData = array_unique(array_merge($existingData, $additionalData));

        return $existingData;
    }

    /**
     * @param array|null $existingData
     * @param array $removeData
     *
     * @return array
     */
    public function removeData($existingData, $removeData)
    {
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $existingData = array_unique(array_diff($existingData, $removeData));

        return $existingData;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @param array|null $value1
     * @param array|null $value2
     *
     * @return bool
     */
    public function isEqual($value1, $value2): bool
    {
        return $this->isEqualArray($value1, $value2);
    }
}
