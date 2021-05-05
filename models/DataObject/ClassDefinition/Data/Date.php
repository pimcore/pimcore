<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Carbon\Carbon;
use Pimcore\Db;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

class Date extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\DefaultValueTrait;

    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'date';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'bigint(20)';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'bigint(20)';

    /**
     * @internal
     *
     * @var int
     */
    public $defaultValue;

    /**
     * @internal
     *
     * @var bool
     */
    public $useCurrentDate;

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param \DateTime $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if ($data) {
            $result = $data->getTimestamp();
            if ($this->getColumnType() == 'date') {
                $result = date('Y-m-d', $result);
            }

            return $result;
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return \Carbon\Carbon|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data) {
            if ($this->getColumnType() == 'date') {
                $data = strtotime($data);
                if ($data === false) {
                    return null;
                }
            }

            $result = $this->getDateFromTimestamp($data);

            return $result;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param \DateTime $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param \DateTime|null $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getTimestamp();
        }

        return null;
    }

    /**
     * @param int $timestamp
     *
     * @return \Carbon\Carbon
     */
    private function getDateFromTimestamp($timestamp)
    {
        $date = new \Carbon\Carbon();
        $date->setTimestamp($timestamp);

        return $date;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param int $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return \Carbon\Carbon|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            return $this->getDateFromTimestamp($data / 1000);
        }

        return null;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return \Carbon\Carbon|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        if ($data) {
            $data = $data * 1000;
        }

        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param Carbon|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return int|null|string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data) {
            return $data->getTimestamp();
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param \DateTime|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof \DateTimeInterface) {
            return $data->format('Y-m-d');
        }

        return '';
    }

    /**
     * @return int
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return $this->defaultValue;
        }

        return 0;
    }

    /**
     * @param mixed $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen((string)$defaultValue) > 0) {
            if (is_numeric($defaultValue)) {
                $this->defaultValue = (int)$defaultValue;
            } else {
                $this->defaultValue = strtotime($defaultValue);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof \DateTimeInterface) {
            return $data->format('Y-m-d');
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * @param bool $useCurrentDate
     *
     * @return $this
     */
    public function setUseCurrentDate($useCurrentDate)
    {
        $this->useCurrentDate = (bool)$useCurrentDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUseCurrentDate(): bool
    {
        return $this->useCurrentDate;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** See parent class.
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        $thedata = $data[0]['data'];
        if ($thedata) {
            return $this->getDateFromTimestamp($thedata);
        }

        return null;
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

        $thedata = null;
        if ($data) {
            $thedata = $data->getTimestamp();
        }
        $diffdata = [];
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;
        $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
        $diffdata['data'] = $thedata;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;
        $diffdata['disabled'] = false;

        $result[] = $diffdata;

        return $result;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param string|int $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        $timestamp = $value;

        if ($this->getColumnType() == 'date') {
            $value = date('Y-m-d', $value);
        }

        if ($operator == '=') {
            $db = Db::get();

            if ($this->getColumnType() == 'date') {
                $condition = $db->quoteIdentifier($params['name']) . ' = '. $db->quote($value);

                return $condition;
            } else {
                $maxTime = $timestamp + (86400 - 1); //specifies the top point of the range used in the condition
                $filterField = $params['name'] ? $params['name'] : $this->getName();
                $condition = '`' . $filterField . '` BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);

                return $condition;
            }
        }

        return parent::getFilterConditionExt($value, $operator, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetDefaultValue($object, $context = [])
    {
        if ($this->getDefaultValue()) {
            $date = new \Carbon\Carbon();
            $date->setTimestamp($this->getDefaultValue());

            return $date;
        } elseif ($this->isUseCurrentDate()) {
            return new \Carbon\Carbon();
        }

        return null;
    }

    /**
     * @param \DateTimeInterface|null $oldValue
     * @param \DateTimeInterface|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof \DateTimeInterface ? $oldValue->format('Y-m-d') : null;
        $newValue = $newValue instanceof \DateTimeInterface ? $newValue->format('Y-m-d') : null;

        return $oldValue === $newValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Carbon::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Carbon::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . Carbon::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Carbon::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof Carbon) {
            return $value->getTimestamp();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if ($value !== null) {
            return $this->getDateFromTimestamp($value);
        }

        return null;
    }

    /**
     * overwrite default implementation to consider columnType & queryColumnType from class config
     *
     * @return array
     */
    public function resolveBlockedVars(): array
    {
        $defaultBlockedVars = [
            'fieldDefinitionsCache'
        ];

        return array_merge($defaultBlockedVars, $this->getBlockedVarsForExport());
    }
}
