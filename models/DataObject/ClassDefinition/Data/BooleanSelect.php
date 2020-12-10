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

class BooleanSelect extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /** storage value for yes */
    const YES_VALUE = 1;
    /** storage value for no */
    const NO_VALUE = -1;
    /** storage value for empty */
    const EMPTY_VALUE = null;
    /** edit mode valze for empty */
    const EMPTY_VALUE_EDITMODE = 0;
    /**
     * Available options to select - Default options
     *
     * @var array
     */
    const DEFAULT_OPTIONS = [
        [
            'key' => 'empty',
            'value' => self::EMPTY_VALUE_EDITMODE,
        ],
        [
            'key' => 'yes',
            'value' => self::YES_VALUE,
        ],
        [
            'key' => 'no',
            'value' => self::NO_VALUE,
        ],
    ];
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'booleanSelect';
    /** @var string */
    public $yesLabel;
    /** @var string */
    public $noLabel;
    /** @var string */
    public $emptyLabel;
    public $options = self::DEFAULT_OPTIONS;

    /**
     * @var int
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'tinyint(1) null';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'tinyint(1) null';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'bool';

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
        // nothing to do
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
     * @param int|null $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            $data = (int) $data;
        }

        if ($data === self::YES_VALUE) {
            return true;
        } elseif ($data === self::NO_VALUE) {
            return false;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param int|bool|null $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param int|bool|null $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            $data = (bool) $data;
        }
        if ($data === true) {
            return self::YES_VALUE;
        } elseif ($data === false) {
            return self::NO_VALUE;
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param string $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data === true) {
            return $this->getYesLabel();
        } elseif ($data === false) {
            return $this->getNoLabel();
        }

        return '';
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
        //TODO mandatory probably doesn't make much sense
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     * @param bool|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return $data !== true && $data !== false;
    }

    /**
     * @param DataObject\ClassDefinition\Data\BooleanSelect $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->options = $masterDefinition->options;
        $this->width = $masterDefinition->width;
    }

    /**
     * @return string
     */
    public function getYesLabel()
    {
        return $this->yesLabel;
    }

    /**
     * @param string|null $yesLabel
     *
     * @return $this
     */
    public function setYesLabel($yesLabel)
    {
        $this->yesLabel = $yesLabel;
        $this->setOptionsEntry(self::YES_VALUE, $yesLabel);

        return $this;
    }

    public function setOptionsEntry($value, $label)
    {
        if (!is_array($this->options)) {
            $this->options = [
                ['key' => $label,
                'value' => $value,
                ],

            ];
        } else {
            foreach ($this->options as $idx => $option) {
                if ($option['value'] == $value) {
                    $option['key'] = $label;
                    $this->options[$idx] = $option;
                    break;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getNoLabel()
    {
        return $this->noLabel;
    }

    public function setNoLabel($noLabel)
    {
        $this->noLabel = $noLabel;
        $this->setOptionsEntry(self::NO_VALUE, $noLabel);

        return $this;
    }

    /**
     * @return string
     */
    public function getEmptyLabel()
    {
        return $this->emptyLabel;
    }

    public function setEmptyLabel($emptyLabel)
    {
        $this->emptyLabel = $emptyLabel;
        $this->setOptionsEntry(self::EMPTY_VALUE_EDITMODE, $emptyLabel);

        return $this;
    }

    /**
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data === true) {
            return self::YES_VALUE;
        } elseif ($data === false) {
            return self::NO_VALUE;
        }

        return self::EMPTY_VALUE_EDITMODE;
    }

    /**
     * @param string $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return bool|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if (intval($data) === 1) {
            return true;
        } elseif (intval($data) === -1) {
            return false;
        }

        return null;
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        return $oldValue === $newValue;
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
        $value = $this->getDataFromObjectParam($object, $params);
        if ($value === null) {
            $value = '';
        } elseif ($value) {
            $value = '1';
        } else {
            $value = '0';
        }

        return $value;
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
        if ($importValue === '1') {
            $value = true;
        } elseif ($importValue === '0') {
            $value = false;
        } else {
            $value = null;
        }

        return $value;
    }
}
